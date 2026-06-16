<?php

use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use Stripe\PaymentIntent;

test('billing reconcile command fulfills unfulfilled succeeded purchases', function () {
    $purchase = BillingPurchase::factory()->create([
        'payment_intent_id' => 'pi_reconcile_001',
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => null,
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieveIntent(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'succeeded',
                'amount' => 799,
                'currency' => 'gbp',
            ]);
        }
    });

    $fulfillment = Mockery::spy(PurchaseFulfillmentService::class);
    app()->instance(PurchaseFulfillmentService::class, $fulfillment);

    $this->artisan('billing:reconcile-purchases')
        ->assertSuccessful();

    $fulfillment->shouldHaveReceived('fulfill')->once();

    expect(BillingPurchaseEvent::query()
        ->where('billing_purchase_id', $purchase->id)
        ->where('event_type', BillingPurchaseEventType::ReconcileAttempt)
        ->exists())->toBeTrue();
});

test('billing reconcile dry run does not fulfill purchases', function () {
    BillingPurchase::factory()->create([
        'payment_intent_id' => 'pi_reconcile_dry',
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => null,
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieveIntent(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'succeeded',
            ]);
        }
    });

    $fulfillment = Mockery::spy(PurchaseFulfillmentService::class);
    app()->instance(PurchaseFulfillmentService::class, $fulfillment);

    $this->artisan('billing:reconcile-purchases --dry-run')
        ->assertSuccessful();

    $fulfillment->shouldNotHaveReceived('fulfill');
});
