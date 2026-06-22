<?php

namespace App\Services\LegacyImport\Submissions;

use App\Enums\SubmissionLockReason;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Submissions\Import\SubmissionImportSource;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use App\Services\LegacyImport\Support\LegacyWhatsAppPhoneParser;
use App\Services\LegacyImport\Support\WordPressSubmissionStatusMapper;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubmissionImportService extends Service
{
    public function __construct(
        private readonly SubmissionLockReconciliationService $lockReconciliation,
    ) {}

    public function import(SubmissionImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('submissions');
        $batchSize = (int) config('mydualist.legacy.import.batch_size', 100);
        $batch = [];

        foreach ($source->records() as $record) {
            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $report, $dryRun);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->processBatch($batch, $report, $dryRun);
        }

        if (! $dryRun) {
            $reconciliation = $this->lockReconciliation->reconcile(false);
            $report->reconciliation = $reconciliation;
        } else {
            $report->reconciliation = $this->lockReconciliation->reconcile(true);
        }

        return $report;
    }

    /**
     * @param  list<WordPressSubmissionRecord>  $batch
     */
    private function processBatch(array $batch, LegacyImportReport $report, bool $dryRun): void
    {
        $listIds = DuaList::query()
            ->whereIn('wp_post_id', collect($batch)->pluck('listWpPostId')->unique()->filter()->all())
            ->pluck('id', 'wp_post_id');

        $orderIds = collect($batch)->pluck('unlockWpOrderId')->filter()->unique()->all();
        $purchaseIds = $orderIds === []
            ? collect()
            : BillingPurchase::query()->whereIn('wp_order_id', $orderIds)->pluck('id', 'wp_order_id');

        foreach ($batch as $record) {
            try {
                $this->importRecord($record, $report, $dryRun, $listIds, $purchaseIds);
            } catch (\Throwable $exception) {
                $report->addFailed($record->summary(), $exception->getMessage());
            }
        }
    }

    /**
     * @param  Collection<int|string, int>  $listIds
     * @param  Collection<int|string, int>  $purchaseIds
     */
    private function importRecord(
        WordPressSubmissionRecord $record,
        LegacyImportReport $report,
        bool $dryRun,
        $listIds,
        $purchaseIds,
    ): void {
        $duaListId = $listIds->get($record->listWpPostId);

        if ($duaListId === null) {
            $report->addFailed($record->summary(), "List wp_post_id {$record->listWpPostId} not found. Import lists first.");

            return;
        }

        $existing = $record->wpPostId !== null
            ? DuaSubmission::withTrashed()->where('wp_post_id', $record->wpPostId)->first()
            : null;

        $statusMap = WordPressSubmissionStatusMapper::map(
            $record->reported,
            $record->visibility,
            $record->status,
            $record->completedAt,
        );

        $phone = LegacyWhatsAppPhoneParser::parse($record->rawPhone);
        $unlockPurchaseId = $record->unlockWpOrderId !== null
            ? $purchaseIds->get($record->unlockWpOrderId)
            : null;

        if ($dryRun) {
            if ($existing !== null) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $duaListId, $existing, $statusMap, $phone, $unlockPurchaseId): void {
            $attributes = [
                'dua_list_id' => $duaListId,
                'first_name' => $record->firstName ?? '',
                'last_name' => $record->lastName ?? '',
                'email' => $record->email,
                'gender' => $record->gender ?? 'unspecified',
                'is_anonymous' => false,
                'whatsapp_country_code' => $phone['whatsapp_country_code'],
                'whatsapp_phone' => $phone['whatsapp_phone'],
                'whatsapp_verified_at' => $phone['is_valid'] ? ($record->createdAt ?? now()) : null,
                'is_personal_dua' => $record->isPersonalDua,
                'is_locked' => $record->isLocked,
                'locked_reason' => $record->isLocked ? SubmissionLockReason::VisibleQuotaExhausted : null,
                'content' => $record->content,
                'status' => $statusMap['status'],
                'completed_at' => $statusMap['completed_at'],
                'hidden_at' => $statusMap['hidden_at'],
                'reported_at' => $statusMap['reported_at'],
            ];

            if ($unlockPurchaseId !== null) {
                $attributes['unlock_purchase_id'] = $unlockPurchaseId;
                $attributes['unlocked_at'] = $record->createdAt ?? now();
                $attributes['is_locked'] = false;
                $attributes['locked_reason'] = null;
            }

            $submission = DuaSubmission::withTrashed()->updateOrCreate(
                ['wp_post_id' => $record->wpPostId],
                $attributes,
            );

            LegacyImportTimestamps::apply($submission, $record->createdAt);

            if ($existing === null) {
                $report->addImported($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }
        });
    }
}
