<?php

namespace App\Domains\Billing\Actions;

use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkBillingPurchaseFulfilledAction
{
    public function __invoke(BillingPurchase $purchase, ?User $actor = null): BillingPurchase
    {
        if ($purchase->status !== BillingPurchaseStatus::Succeeded) {
            throw new RuntimeException('Only succeeded purchases can be marked as fulfilled.');
        }

        if ($purchase->fulfilled_at !== null) {
            return $purchase;
        }

        return DB::transaction(function () use ($purchase, $actor): BillingPurchase {
            $locked = BillingPurchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->fulfilled_at !== null) {
                return $locked;
            }

            $locked->forceFill(['fulfilled_at' => now()])->save();

            BillingPurchaseEvent::query()->firstOrCreate(
                [
                    'billing_purchase_id' => $locked->id,
                    'idempotency_key' => 'admin:marked_fulfilled:'.$locked->id,
                ],
                [
                    'event_type' => BillingPurchaseEventType::AdminMarkedFulfilled,
                    'processed_at' => now(),
                    'payload' => [
                        'purchase_id' => $locked->id,
                        'marked_by' => $actor?->id,
                    ],
                ],
            );

            return $locked->fresh();
        });
    }
}
