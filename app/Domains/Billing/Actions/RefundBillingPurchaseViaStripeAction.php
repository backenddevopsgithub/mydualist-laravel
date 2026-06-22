<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Services\PurchaseReversalService;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingPurchaseEventType;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundBillingPurchaseViaStripeAction
{
    public function __construct(
        private readonly StripePaymentIntentService $stripe,
        private readonly PurchaseReversalService $reversal,
    ) {}

    public function __invoke(BillingPurchase $purchase, ?User $actor = null): BillingPurchase
    {
        if ($purchase->refunded_at !== null) {
            return $purchase;
        }

        $paymentIntentId = $purchase->payment_intent_id;

        if ($paymentIntentId === null || $paymentIntentId === '') {
            throw new RuntimeException('This purchase has no Stripe payment intent to refund.');
        }

        $refund = $this->stripe->refundPaymentIntent($paymentIntentId);

        return DB::transaction(function () use ($purchase, $actor, $refund): BillingPurchase {
            $locked = BillingPurchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->refunded_at !== null) {
                return $locked;
            }

            $locked->forceFill(['refunded_at' => now()])->save();

            $chargePayload = [
                'amount_refunded' => $refund['amount'],
                'currency' => $locked->currency,
                'source' => 'admin_stripe',
                'marked_by' => $actor?->id,
                'stripe_refund_id' => $refund['id'],
                'stripe_refund_status' => $refund['status'],
            ];

            BillingPurchaseEvent::query()->firstOrCreate(
                [
                    'billing_purchase_id' => $locked->id,
                    'idempotency_key' => 'admin:stripe_refund:'.$locked->id,
                ],
                [
                    'event_type' => BillingPurchaseEventType::AdminStripeRefund,
                    'processed_at' => now(),
                    'payload' => $chargePayload,
                ],
            );

            $this->reversal->handleRefund($locked->fresh(), $chargePayload);

            return $locked->fresh();
        });
    }
}
