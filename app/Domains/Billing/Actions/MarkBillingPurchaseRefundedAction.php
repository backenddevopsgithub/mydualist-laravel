<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Services\PurchaseReversalService;
use App\Enums\BillingPurchaseEventType;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkBillingPurchaseRefundedAction
{
    public function __construct(
        private readonly PurchaseReversalService $reversal,
    ) {}

    public function __invoke(BillingPurchase $purchase, ?User $actor = null): BillingPurchase
    {
        if ($purchase->refunded_at !== null) {
            return $purchase;
        }

        return DB::transaction(function () use ($purchase, $actor): BillingPurchase {
            $locked = BillingPurchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->refunded_at !== null) {
                return $locked;
            }

            $locked->forceFill(['refunded_at' => now()])->save();

            $chargePayload = [
                'amount_refunded' => $locked->amount_minor,
                'currency' => $locked->currency,
                'source' => 'admin',
                'marked_by' => $actor?->id,
            ];

            BillingPurchaseEvent::query()->firstOrCreate(
                [
                    'billing_purchase_id' => $locked->id,
                    'idempotency_key' => 'admin:marked_refunded:'.$locked->id,
                ],
                [
                    'event_type' => BillingPurchaseEventType::AdminMarkedRefunded,
                    'processed_at' => now(),
                    'payload' => $chargePayload,
                ],
            );

            $this->reversal->handleRefund($locked->fresh(), $chargePayload);

            return $locked->fresh();
        });
    }
}
