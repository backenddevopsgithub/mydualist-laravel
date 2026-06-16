<?php

use App\Domains\Billing\Services\StripeCheckoutService;
use App\Enums\BillingPurchaseEventType;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use Stripe\Event;

function bindRefundDisputeStripeWebhookVerifier(Event $event): void
{
    app()->instance(StripeCheckoutService::class, new class($event) extends StripeCheckoutService
    {
        public function __construct(private readonly Event $event) {}

        public function constructWebhookEvent(string $payload, string $signature): Event
        {
            return $this->event;
        }
    });
}

/**
 * @param  array<string, mixed>  $chargeOrDispute
 * @return array<string, mixed>
 */
function stripeChargeWebhookEvent(
    string $eventId,
    string $eventType,
    array $chargeOrDispute,
): array {
    return [
        'id' => $eventId,
        'type' => $eventType,
        'data' => [
            'object' => $chargeOrDispute,
        ],
    ];
}

test('charge.refunded sets refunded_at and records reversal metadata', function () {
    $purchase = BillingPurchase::factory()->succeeded()->create([
        'payment_intent_id' => 'pi_refund_001',
    ]);

    bindRefundDisputeStripeWebhookVerifier(Event::constructFrom(stripeChargeWebhookEvent(
        'evt_refund_001',
        'charge.refunded',
        [
            'id' => 'ch_refund_001',
            'payment_intent' => 'pi_refund_001',
            'amount_refunded' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'refunded' => true,
        ],
    )));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->refunded_at)->not->toBeNull()
        ->and($purchase->metadata['reversal']['requires_entitlement_review'])->toBeTrue();

    $this->assertDatabaseHas('billing_purchase_events', [
        'billing_purchase_id' => $purchase->id,
        'stripe_event_id' => 'evt_refund_001',
        'event_type' => BillingPurchaseEventType::ChargeRefunded->value,
    ]);
});

test('charge.dispute.created sets disputed_at and records dispute metadata', function () {
    $purchase = BillingPurchase::factory()->succeeded()->create([
        'payment_intent_id' => 'pi_dispute_001',
    ]);

    bindRefundDisputeStripeWebhookVerifier(Event::constructFrom(stripeChargeWebhookEvent(
        'evt_dispute_001',
        'charge.dispute.created',
        [
            'id' => 'dp_dispute_001',
            'payment_intent' => 'pi_dispute_001',
            'reason' => 'fraudulent',
            'status' => 'needs_response',
        ],
    )));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->disputed_at)->not->toBeNull()
        ->and($purchase->metadata['reversal']['dispute_id'])->toBe('dp_dispute_001');

    $this->assertDatabaseHas('billing_purchase_events', [
        'billing_purchase_id' => $purchase->id,
        'stripe_event_id' => 'evt_dispute_001',
        'event_type' => BillingPurchaseEventType::ChargeDisputeCreated->value,
    ]);
});

test('duplicate refund events do not overwrite refunded_at twice', function () {
    $purchase = BillingPurchase::factory()->succeeded()->create([
        'payment_intent_id' => 'pi_refund_dup',
        'refunded_at' => now()->subDay(),
    ]);

    $originalRefundedAt = $purchase->refunded_at->toIso8601String();

    $eventPayload = stripeChargeWebhookEvent(
        'evt_refund_dup',
        'charge.refunded',
        [
            'id' => 'ch_refund_dup',
            'payment_intent' => 'pi_refund_dup',
            'amount_refunded' => $purchase->amount_minor,
            'currency' => $purchase->currency,
        ],
    );

    bindRefundDisputeStripeWebhookVerifier(Event::constructFrom($eventPayload));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    bindRefundDisputeStripeWebhookVerifier(Event::constructFrom($eventPayload));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->refunded_at->toIso8601String())->toBe($originalRefundedAt)
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('stripe_event_id', 'evt_refund_dup')
            ->count())->toBe(1);
});
