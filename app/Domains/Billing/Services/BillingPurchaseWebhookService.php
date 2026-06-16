<?php

namespace App\Domains\Billing\Services;

use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Stripe\Event;

class BillingPurchaseWebhookService extends Service
{
    public function __construct(
        private readonly PurchaseFulfillmentService $fulfillment,
        private readonly PurchaseReversalService $reversal,
    ) {}

    /**
     * @var array<string, BillingPurchaseEventType>
     */
    private const PAYMENT_INTENT_EVENTS = [
        'payment_intent.succeeded' => BillingPurchaseEventType::PaymentIntentSucceeded,
        'payment_intent.payment_failed' => BillingPurchaseEventType::PaymentIntentFailed,
    ];

    /**
     * @var array<string, BillingPurchaseEventType>
     */
    private const CHARGE_EVENTS = [
        'charge.refunded' => BillingPurchaseEventType::ChargeRefunded,
    ];

    /**
     * @var array<string, BillingPurchaseEventType>
     */
    private const DISPUTE_EVENTS = [
        'charge.dispute.created' => BillingPurchaseEventType::ChargeDisputeCreated,
    ];

    public function handle(Event $event): void
    {
        if (isset(self::PAYMENT_INTENT_EVENTS[$event->type])) {
            $this->handlePaymentIntentEvent($event);

            return;
        }

        if (isset(self::CHARGE_EVENTS[$event->type]) || isset(self::DISPUTE_EVENTS[$event->type])) {
            $this->handleChargeOrDisputeEvent($event);
        }
    }

    private function handlePaymentIntentEvent(Event $event): void
    {
        $paymentIntentId = (string) data_get($event->data->object, 'id');

        if ($paymentIntentId === '') {
            return;
        }

        $purchase = BillingPurchase::query()
            ->where('payment_intent_id', $paymentIntentId)
            ->first();

        if (! $purchase) {
            return;
        }

        DB::transaction(function () use ($purchase, $event): void {
            $lockedPurchase = BillingPurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPurchase) {
                return;
            }

            if ($this->eventAlreadyProcessed($lockedPurchase, $event->id)) {
                return;
            }

            $previousStatus = $lockedPurchase->status;

            BillingPurchaseEvent::query()->create([
                'billing_purchase_id' => $lockedPurchase->id,
                'event_type' => self::PAYMENT_INTENT_EVENTS[$event->type],
                'stripe_event_id' => $event->id,
                'idempotency_key' => 'stripe_event:'.$event->id,
                'payload' => $this->payloadSnapshot($event),
                'processed_at' => now(),
            ]);

            $updates = [
                'status' => $this->statusForPaymentIntentEvent($event->type),
            ];

            if ($event->type === 'payment_intent.payment_failed') {
                $updates['failure_reason'] = (string) (
                    data_get($event->data->object, 'last_payment_error.message')
                    ?? 'Payment failed.'
                );
            }

            $lockedPurchase->update($updates);

            if (
                $event->type === 'payment_intent.succeeded'
                && $previousStatus !== BillingPurchaseStatus::Succeeded
            ) {
                $this->fulfillment->fulfill($lockedPurchase->fresh(['product', 'user']));
            }
        });
    }

    private function handleChargeOrDisputeEvent(Event $event): void
    {
        $paymentIntentId = (string) data_get($event->data->object, 'payment_intent');

        if ($paymentIntentId === '') {
            return;
        }

        $purchase = BillingPurchase::query()
            ->where('payment_intent_id', $paymentIntentId)
            ->first();

        if (! $purchase) {
            return;
        }

        $eventType = self::CHARGE_EVENTS[$event->type]
            ?? self::DISPUTE_EVENTS[$event->type];

        DB::transaction(function () use ($purchase, $event, $eventType): void {
            $lockedPurchase = BillingPurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPurchase || $this->eventAlreadyProcessed($lockedPurchase, $event->id)) {
                return;
            }

            $objectPayload = json_decode(json_encode($event->data->object, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            BillingPurchaseEvent::query()->create([
                'billing_purchase_id' => $lockedPurchase->id,
                'event_type' => $eventType,
                'stripe_event_id' => $event->id,
                'idempotency_key' => 'stripe_event:'.$event->id,
                'payload' => $this->payloadSnapshot($event),
                'processed_at' => now(),
            ]);

            if ($event->type === 'charge.refunded' && $lockedPurchase->refunded_at === null) {
                $lockedPurchase->update(['refunded_at' => now()]);
                $this->reversal->handleRefund($lockedPurchase->fresh(), $objectPayload);
            }

            if ($event->type === 'charge.dispute.created' && $lockedPurchase->disputed_at === null) {
                $lockedPurchase->update(['disputed_at' => now()]);
                $this->reversal->handleDisputeOpened($lockedPurchase->fresh(), $objectPayload);
            }
        });
    }

    private function eventAlreadyProcessed(BillingPurchase $purchase, string $stripeEventId): bool
    {
        return BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('stripe_event_id', $stripeEventId)
            ->exists();
    }

    private function statusForPaymentIntentEvent(string $eventType): BillingPurchaseStatus
    {
        return match ($eventType) {
            'payment_intent.succeeded' => BillingPurchaseStatus::Succeeded,
            'payment_intent.payment_failed' => BillingPurchaseStatus::Failed,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadSnapshot(Event $event): array
    {
        return json_decode($event->toJSON(), true, 512, JSON_THROW_ON_ERROR);
    }
}
