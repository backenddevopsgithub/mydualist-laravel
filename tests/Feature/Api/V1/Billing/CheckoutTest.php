<?php

use App\Domains\Billing\Services\StripeCheckoutService;
use App\Models\StripePayment;
use App\Models\User;
use App\Models\UserEntitlement;
use Stripe\Checkout\Session;

test('billing checkout api requires authentication', function () {
    $this->postJson('/api/v1/billing/checkout')->assertUnauthorized();
});

test('authenticated user can start premium checkout via api', function () {
    $user = $this->actingAsUser();

    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function createPremiumCheckout(User $user, ?string $successUrl = null, ?string $cancelUrl = null): array
        {
            return [
                'id' => 'cs_api_checkout',
                'url' => 'https://checkout.stripe.test/api-session',
                'amount_total' => 1199,
                'currency' => 'usd',
            ];
        }
    });

    $this->postJson('/api/v1/billing/checkout', [
        'success_url' => 'mydualist://billing/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'mydualist://billing/cancel',
    ])->assertCreated()
        ->assertJsonPath('data.session_id', 'cs_api_checkout')
        ->assertJsonPath('data.checkout_url', 'https://checkout.stripe.test/api-session')
        ->assertJsonPath('data.amount_total', 1199);

    $this->assertDatabaseHas('stripe_payments', [
        'user_id' => $user->id,
        'stripe_checkout_session_id' => 'cs_api_checkout',
        'status' => StripePayment::STATUS_PENDING,
    ]);
});

test('checkout status api verifies ownership and fulfills premium', function () {
    $user = $this->actingAsUser();

    StripePayment::query()->create([
        'user_id' => $user->id,
        'stripe_checkout_session_id' => 'cs_api_status',
        'amount_total' => 1199,
        'currency' => 'usd',
        'status' => StripePayment::STATUS_PENDING,
        'metadata' => ['entitlement' => 'premium'],
    ]);

    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function retrieveCheckoutSession(string $sessionId): mixed
        {
            return Session::constructFrom([
                'id' => $sessionId,
                'client_reference_id' => (string) auth()->id(),
                'payment_status' => 'paid',
                'payment_intent' => 'pi_api_status',
                'amount_total' => 1199,
                'currency' => 'usd',
                'metadata' => ['user_id' => (string) auth()->id()],
            ]);
        }
    });

    $this->getJson('/api/v1/billing/checkout/cs_api_status')
        ->assertOk()
        ->assertJsonPath('data.session_id', 'cs_api_status')
        ->assertJsonPath('data.payment_status', 'paid')
        ->assertJsonPath('data.status', StripePayment::STATUS_PAID)
        ->assertJsonPath('data.has_premium', true)
        ->assertJsonPath('data.fulfilled', true);

    $this->assertDatabaseHas('user_entitlements', [
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'reference' => 'cs_api_status',
        'active' => true,
    ]);
});

test('checkout status api rejects another users session', function () {
    $owner = User::factory()->create();
    $this->actingAsUser();

    StripePayment::query()->create([
        'user_id' => $owner->id,
        'stripe_checkout_session_id' => 'cs_other_user',
        'amount_total' => 1199,
        'currency' => 'usd',
        'status' => StripePayment::STATUS_PENDING,
        'metadata' => ['entitlement' => 'premium'],
    ]);

    $this->getJson('/api/v1/billing/checkout/cs_other_user')->assertNotFound();
});

test('checkout status api returns pending state before payment completes', function () {
    $user = $this->actingAsUser();

    StripePayment::query()->create([
        'user_id' => $user->id,
        'stripe_checkout_session_id' => 'cs_api_pending',
        'amount_total' => 1199,
        'currency' => 'usd',
        'status' => StripePayment::STATUS_PENDING,
        'metadata' => ['entitlement' => 'premium'],
    ]);

    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function retrieveCheckoutSession(string $sessionId): mixed
        {
            return Session::constructFrom([
                'id' => $sessionId,
                'client_reference_id' => (string) auth()->id(),
                'payment_status' => 'unpaid',
                'amount_total' => 1199,
                'currency' => 'usd',
                'metadata' => ['user_id' => (string) auth()->id()],
            ]);
        }
    });

    $this->getJson('/api/v1/billing/checkout/cs_api_pending')
        ->assertOk()
        ->assertJsonPath('data.payment_status', 'unpaid')
        ->assertJsonPath('data.status', StripePayment::STATUS_PENDING)
        ->assertJsonPath('data.has_premium', false)
        ->assertJsonPath('data.fulfilled', false);
});

test('billing checkout api validates optional redirect urls', function () {
    $this->actingAsUser();

    $this->postJson('/api/v1/billing/checkout', [
        'success_url' => 'not-a-valid-url',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['success_url']);
});
