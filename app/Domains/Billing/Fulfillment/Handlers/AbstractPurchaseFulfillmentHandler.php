<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Domains\Billing\Fulfillment\Contracts\PurchaseFulfillmentHandler;
use App\Domains\Billing\Fulfillment\SubmissionUnlockService;
use App\Models\BillingPurchase;
use App\Models\EntitlementGrant;
use RuntimeException;

abstract class AbstractPurchaseFulfillmentHandler implements PurchaseFulfillmentHandler
{
    public function __construct(
        protected readonly SubmissionUnlockService $unlocker,
    ) {}

    protected function requireUser(BillingPurchase $purchase): void
    {
        if ($purchase->user_id === null) {
            throw new RuntimeException('Purchase fulfillment requires an authenticated user.');
        }
    }

    protected function requireList(BillingPurchase $purchase): void
    {
        if ($purchase->dua_list_id === null) {
            throw new RuntimeException('Purchase fulfillment requires a list target.');
        }
    }

    protected function grantFromPurchase(BillingPurchase $purchase, array $attributes): EntitlementGrant
    {
        $existing = EntitlementGrant::query()
            ->where('source_purchase_id', $purchase->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return EntitlementGrant::query()->create(array_merge($attributes, [
            'user_id' => $purchase->user_id,
            'source_purchase_id' => $purchase->id,
            'granted_at' => now(),
            'dedupe_key' => $attributes['dedupe_key'] ?? EntitlementGrant::dedupeKeyForPurchase($purchase->id),
        ]));
    }

    protected function grantUniqueListEntitlement(BillingPurchase $purchase, array $attributes): EntitlementGrant
    {
        $existing = EntitlementGrant::query()
            ->where('source_purchase_id', $purchase->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return EntitlementGrant::query()->firstOrCreate(
            ['dedupe_key' => $attributes['dedupe_key']],
            array_merge($attributes, [
                'user_id' => $purchase->user_id,
                'source_purchase_id' => $purchase->id,
                'granted_at' => now(),
            ]),
        );
    }

    protected function grantUniqueUserEntitlement(BillingPurchase $purchase, array $attributes): EntitlementGrant
    {
        $existing = EntitlementGrant::query()
            ->where('source_purchase_id', $purchase->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return EntitlementGrant::query()->firstOrCreate(
            ['dedupe_key' => $attributes['dedupe_key']],
            array_merge($attributes, [
                'user_id' => $purchase->user_id,
                'source_purchase_id' => $purchase->id,
                'granted_at' => now(),
            ]),
        );
    }
}
