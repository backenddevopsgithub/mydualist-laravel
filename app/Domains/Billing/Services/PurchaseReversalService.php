<?php

namespace App\Domains\Billing\Services;

use App\Models\BillingPurchase;
use App\Services\Service;
use Illuminate\Support\Facades\Log;

class PurchaseReversalService extends Service
{
    /**
     * Records a refund and queues operational follow-up.
     *
     * Entitlement reversal is intentionally deferred to manual review until
     * reversal handlers are approved for production rollout.
     *
     * @param  array<string, mixed>  $chargePayload
     */
    public function handleRefund(BillingPurchase $purchase, array $chargePayload): void
    {
        Log::warning('billing.purchase.refunded', [
            'billing_purchase_id' => $purchase->id,
            'payment_intent_id' => $purchase->payment_intent_id,
            'amount_refunded' => data_get($chargePayload, 'amount_refunded'),
            'currency' => data_get($chargePayload, 'currency'),
        ]);

        $metadata = $purchase->metadata ?? [];
        $metadata['reversal'] = array_merge($metadata['reversal'] ?? [], [
            'refund_recorded_at' => now()->toIso8601String(),
            'requires_entitlement_review' => $purchase->isFulfilled(),
        ]);

        $purchase->forceFill(['metadata' => $metadata])->save();
    }

    /**
     * @param  array<string, mixed>  $disputePayload
     */
    public function handleDisputeOpened(BillingPurchase $purchase, array $disputePayload): void
    {
        Log::warning('billing.purchase.disputed', [
            'billing_purchase_id' => $purchase->id,
            'payment_intent_id' => $purchase->payment_intent_id,
            'dispute_id' => data_get($disputePayload, 'id'),
            'reason' => data_get($disputePayload, 'reason'),
            'status' => data_get($disputePayload, 'status'),
        ]);

        $metadata = $purchase->metadata ?? [];
        $metadata['reversal'] = array_merge($metadata['reversal'] ?? [], [
            'dispute_opened_at' => now()->toIso8601String(),
            'dispute_id' => data_get($disputePayload, 'id'),
            'dispute_reason' => data_get($disputePayload, 'reason'),
        ]);

        $purchase->forceFill(['metadata' => $metadata])->save();
    }
}
