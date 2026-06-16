<?php

use App\Domains\Billing\Services\StripeCheckoutService;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

/**
 * @param  array<string, mixed>  $paymentIntent
 * @return array<string, mixed>
 */
function stripePaymentIntentWebhookEvent(
    string $eventId,
    string $eventType,
    array $paymentIntent,
): array {
    return [
        'id' => $eventId,
        'type' => $eventType,
        'data' => [
            'object' => $paymentIntent,
        ],
    ];
}

function bindStripeWebhookVerifier(Event $event): void
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

function bindInvalidStripeWebhookVerifier(): void
{
    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function constructWebhookEvent(string $payload, string $signature): Event
        {
            throw new SignatureVerificationException('Invalid signature.');
        }
    });
}

test('billing webhook rejects invalid signatures', function () {
    bindInvalidStripeWebhookVerifier();

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'invalid',
    ])->assertStatus(400);
});

test('billing webhook ignores unhandled event types', function () {
    bindStripeWebhookVerifier(Event::constructFrom([
        'id' => 'evt_unhandled',
        'type' => 'customer.created',
        'data' => ['object' => ['id' => 'cus_test']],
    ]));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    expect(BillingPurchaseEvent::query()->count())->toBe(0);
});

test('billing webhook ignores unknown payment intent ids gracefully', function () {
    bindStripeWebhookVerifier(Event::constructFrom(stripePaymentIntentWebhookEvent(
        'evt_unknown_pi',
        'payment_intent.succeeded',
        ['id' => 'pi_unknown'],
    )));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    expect(BillingPurchaseEvent::query()->count())->toBe(0);
});

test('payment_intent.succeeded updates purchase status and persists event snapshot', function () {
    $purchase = BillingPurchase::factory()->create([
        'payment_intent_id' => 'pi_success_001',
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
    ]);

    bindStripeWebhookVerifier(Event::constructFrom(stripePaymentIntentWebhookEvent(
        'evt_success_001',
        'payment_intent.succeeded',
        [
            'id' => 'pi_success_001',
            'amount' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'status' => 'succeeded',
            'metadata' => [
                'billing_purchase_id' => (string) $purchase->id,
            ],
        ],
    )));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded);

    $event = BillingPurchaseEvent::query()
        ->where('billing_purchase_id', $purchase->id)
        ->where('stripe_event_id', 'evt_success_001')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->event_type)->toBe(BillingPurchaseEventType::PaymentIntentSucceeded)
        ->and($event->idempotency_key)->toBe('stripe_event:evt_success_001')
        ->and($event->processed_at)->not->toBeNull()
        ->and($event->payload)->toBeArray()
        ->and($event->payload['id'])->toBe('evt_success_001')
        ->and($event->payload['type'])->toBe('payment_intent.succeeded')
        ->and($event->payload['data']['object']['id'])->toBe('pi_success_001');
});

test('payment_intent.payment_failed updates purchase status and stores failure reason', function () {
    $purchase = BillingPurchase::factory()->create([
        'payment_intent_id' => 'pi_failed_001',
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
    ]);

    bindStripeWebhookVerifier(Event::constructFrom(stripePaymentIntentWebhookEvent(
        'evt_failed_001',
        'payment_intent.payment_failed',
        [
            'id' => 'pi_failed_001',
            'amount' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'status' => 'requires_payment_method',
            'last_payment_error' => [
                'message' => 'Your card was declined.',
            ],
        ],
    )));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Failed)
        ->and($purchase->failure_reason)->toBe('Your card was declined.');

    $this->assertDatabaseHas('billing_purchase_events', [
        'billing_purchase_id' => $purchase->id,
        'stripe_event_id' => 'evt_failed_001',
        'event_type' => BillingPurchaseEventType::PaymentIntentFailed->value,
    ]);
});

test('duplicate stripe events are ignored safely', function () {
    $purchase = BillingPurchase::factory()->create([
        'payment_intent_id' => 'pi_duplicate_001',
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
    ]);

    $eventPayload = stripePaymentIntentWebhookEvent(
        'evt_duplicate_001',
        'payment_intent.succeeded',
        [
            'id' => 'pi_duplicate_001',
            'amount' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'status' => 'succeeded',
        ],
    );

    bindStripeWebhookVerifier(Event::constructFrom($eventPayload));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    bindStripeWebhookVerifier(Event::constructFrom($eventPayload));

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    expect(BillingPurchaseEvent::query()
        ->where('billing_purchase_id', $purchase->id)
        ->where('stripe_event_id', 'evt_duplicate_001')
        ->count())->toBe(1);

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded);
});

test('billing webhook returns 503 when stripe webhook secret is not configured', function () {
    config(['services.stripe.webhook_secret' => null]);

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertStatus(503);
});
