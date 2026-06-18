<?php

namespace App\Services\LegacyImport\Purchases;

use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Purchases\Import\PurchaseImportSource;
use App\Services\Service;
use Illuminate\Support\Facades\DB;

class PurchaseImportService extends Service
{
    public function __construct(
        private readonly PurchaseFulfillmentService $fulfillmentService,
    ) {}

    public function import(PurchaseImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('purchases');
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

        return $report;
    }

    /**
     * @param  list<WordPressOrderRecord>  $batch
     */
    private function processBatch(array $batch, LegacyImportReport $report, bool $dryRun): void
    {
        foreach ($batch as $record) {
            try {
                $this->importRecord($record, $report, $dryRun);
            } catch (\Throwable $exception) {
                $report->addFailed($record->summary(), $exception->getMessage());
            }
        }
    }

    private function importRecord(WordPressOrderRecord $record, LegacyImportReport $report, bool $dryRun): void
    {
        $product = BillingProduct::query()
            ->where('external_product_id', $record->productExternalId)
            ->first();

        if ($product === null) {
            $report->addFailed($record->summary(), "No billing product mapped to external_id {$record->productExternalId}.");

            return;
        }

        $user = $record->customerWpLegacyId !== null
            ? User::query()->where('wp_legacy_id', $record->customerWpLegacyId)->first()
            : null;

        $productCode = BillingProductCode::tryFrom((string) $product->code);
        $requiresUser = $productCode !== BillingProductCode::CommunityDuaPaid;

        if ($requiresUser && $user === null) {
            $report->addFailed($record->summary(), "Customer wp_legacy_id {$record->customerWpLegacyId} not found.");

            return;
        }

        $duaList = null;
        if ($record->listWpPostId !== null) {
            $duaList = DuaList::query()->where('wp_post_id', $record->listWpPostId)->first();

            if ($duaList === null && in_array($productCode, [
                BillingProductCode::RequestPack25,
                BillingProductCode::UnlimitedOneList,
            ], true)) {
                $report->addFailed($record->summary(), "List wp_post_id {$record->listWpPostId} not found.");

                return;
            }
        }

        $communityDua = null;

        $existing = BillingPurchase::query()->where('wp_order_id', $record->wpOrderId)->exists();

        if ($dryRun) {
            if ($existing) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $existing, $product, $user, $duaList, $communityDua, $productCode): void {
            $attributes = [
                'billing_product_id' => $product->id,
                'user_id' => $user?->id,
                'dua_list_id' => $duaList?->id,
                'community_dua_id' => $communityDua?->id,
                'status' => BillingPurchaseStatus::Succeeded,
                'payment_intent_id' => 'wp_order_'.$record->wpOrderId,
                'amount_minor' => $record->amountMinor,
                'currency' => $record->currency,
                'idempotency_key' => 'wp-order:'.$record->wpOrderId,
                'metadata' => [
                    'wp_order_id' => $record->wpOrderId,
                    'product_external_id' => $record->productExternalId,
                ],
            ];

            if ($record->createdAt !== null) {
                $attributes['created_at'] = $record->createdAt;
                $attributes['updated_at'] = $record->createdAt;
            }

            $purchase = BillingPurchase::query()->updateOrCreate(
                ['wp_order_id' => $record->wpOrderId],
                $attributes,
            );

            if ($this->canFulfill($purchase, $productCode, $duaList, $communityDua)) {
                $this->fulfillmentService->fulfill($purchase->fresh(['product', 'user']));
            } else {
                $report->addSkipped($record->summary(), 'Fulfillment deferred until related records exist.');
            }

            if ($existing) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }
        });
    }

    private function canFulfill(
        BillingPurchase $purchase,
        ?BillingProductCode $productCode,
        ?DuaList $duaList,
        ?CommunityDua $communityDua,
    ): bool {
        return match ($productCode) {
            BillingProductCode::RequestPack25,
            BillingProductCode::UnlimitedOneList => $duaList !== null,
            BillingProductCode::AdditionalList,
            BillingProductCode::UnlimitedForever => $purchase->user_id !== null,
            BillingProductCode::CommunityDuaPaid => false,
            default => false,
        };
    }
}
