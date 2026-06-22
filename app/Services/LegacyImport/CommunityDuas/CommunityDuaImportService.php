<?php

namespace App\Services\LegacyImport\CommunityDuas;

use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\CommunityDuaCompletion;
use App\Models\CommunityDuaQueueState;
use App\Models\CommunityDuaSkip;
use App\Models\User;
use App\Services\LegacyImport\CommunityDuas\Import\CommunityDuaImportSource;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommunityDuaImportService extends Service
{
    public function __construct(
        private readonly PurchaseFulfillmentService $fulfillmentService,
    ) {}

    public function import(CommunityDuaImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('community_duas');
        $duaBatch = [];
        $batchSize = (int) config('mydualist.legacy.import.batch_size', 100);

        foreach ($source->duaRecords() as $record) {
            $duaBatch[] = $record;

            if (count($duaBatch) >= $batchSize) {
                $this->processDuaBatch($duaBatch, $report, $dryRun);
                $duaBatch = [];
            }
        }

        if ($duaBatch !== []) {
            $this->processDuaBatch($duaBatch, $report, $dryRun);
        }

        if (! $dryRun) {
            $this->importQueueStates($source, $report);
        }

        return $report;
    }

    /**
     * @param  list<WordPressCommunityDuaRecord>  $batch
     */
    private function processDuaBatch(array $batch, LegacyImportReport $report, bool $dryRun): void
    {
        $orderIds = collect($batch)->pluck('wpOrderId')->filter()->unique()->all();
        $purchases = $orderIds === []
            ? collect()
            : BillingPurchase::query()->whereIn('wp_order_id', $orderIds)->get()->keyBy('wp_order_id');

        foreach ($batch as $record) {
            try {
                $this->importDua($record, $report, $dryRun, $purchases);
            } catch (\Throwable $exception) {
                $report->addFailed($record->summary(), $exception->getMessage());
            }
        }
    }

    /**
     * @param  Collection<int|string, BillingPurchase>  $purchases
     */
    private function importDua(
        WordPressCommunityDuaRecord $record,
        LegacyImportReport $report,
        bool $dryRun,
        $purchases,
    ): void {
        $existing = CommunityDua::query()->where('wp_post_id', $record->wpPostId)->first();
        $purchase = $record->wpOrderId !== null ? $purchases->get($record->wpOrderId) : null;

        if ($dryRun) {
            if ($existing !== null) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $existing, $purchase): void {
            $attributes = [
                'first_name' => mb_substr($record->firstName, 0, 15),
                'last_name' => mb_substr($record->lastName, 0, 15),
                'email' => $record->email,
                'gender' => $record->gender,
                'content' => $record->content,
                'type' => $record->type,
                'status' => $record->status,
                'required_completions' => $record->requiredCompletions,
                'completion_count' => $record->completionCount,
                'is_visible' => $record->isVisible,
                'fulfilled_at' => $record->type === CommunityDuaType::Free
                    && $record->status === CommunityDuaStatus::Active
                    ? ($record->createdAt ?? now())
                    : null,
            ];

            $communityDua = CommunityDua::query()->updateOrCreate(
                ['wp_post_id' => $record->wpPostId],
                $attributes,
            );

            LegacyImportTimestamps::apply($communityDua, $record->createdAt);

            if ($purchase !== null && $purchase->community_dua_id === null) {
                $purchase->forceFill(['community_dua_id' => $communityDua->id])->save();
            }

            if ($purchase !== null && $purchase->isUnfulfilled()) {
                $this->fulfillmentService->fulfill($purchase->fresh(['product', 'user']));
            }

            if ($existing === null) {
                $report->addImported($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }
        });
    }

    private function importQueueStates(CommunityDuaImportSource $source, LegacyImportReport $report): void
    {
        $duaIdsByWpPost = CommunityDua::query()
            ->whereNotNull('wp_post_id')
            ->pluck('id', 'wp_post_id');

        $userIdsByWpLegacy = User::query()
            ->whereNotNull('wp_legacy_id')
            ->pluck('id', 'wp_legacy_id');

        foreach ($source->queueRecords() as $queueRecord) {
            $userId = $userIdsByWpLegacy->get($queueRecord->userWpLegacyId);

            if ($userId === null) {
                $report->addSkipped([
                    'user_wp_legacy_id' => $queueRecord->userWpLegacyId,
                ], 'User not found for community queue state.');

                continue;
            }

            $currentDuaId = $queueRecord->seeingNowWpPostId !== null
                ? $duaIdsByWpPost->get($queueRecord->seeingNowWpPostId)
                : null;

            CommunityDuaQueueState::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'showing_type' => in_array($queueRecord->showingType, ['free', 'paid'], true)
                        ? $queueRecord->showingType
                        : 'paid',
                    'pattern' => $queueRecord->pattern,
                    'current_community_dua_id' => $currentDuaId,
                ],
            );

            foreach ($queueRecord->completedWpPostIds as $wpPostId) {
                $duaId = $duaIdsByWpPost->get($wpPostId);

                if ($duaId === null) {
                    continue;
                }

                CommunityDuaCompletion::query()->firstOrCreate([
                    'community_dua_id' => $duaId,
                    'user_id' => $userId,
                ]);
            }

            foreach ($queueRecord->seenWpPostIds as $wpPostId) {
                $duaId = $duaIdsByWpPost->get($wpPostId);

                if ($duaId === null) {
                    continue;
                }

                if (in_array($wpPostId, $queueRecord->completedWpPostIds, true)) {
                    continue;
                }

                CommunityDuaSkip::query()->firstOrCreate([
                    'community_dua_id' => $duaId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
