<?php

use App\Domains\Billing\Services\StripeCheckoutService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\StripePayment;
use App\Models\User;
use App\Models\UserEntitlement;
use Illuminate\Support\Facades\Notification;
use Stripe\Checkout\Session;
use Stripe\Event;

test('free users are blocked from creating more than two active lists', function () {
    $user = User::factory()->create();

    DuaList::factory()->count(2)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/create-list')
        ->assertRedirect(route('dashboard.upgrade'))
        ->assertSessionHasErrors('billing');
});

test('premium users can create beyond the free list limit', function () {
    Notification::fake();

    $user = User::factory()->create();
    DuaList::factory()->count(2)->create(['user_id' => $user->id]);
    UserEntitlement::query()->create([
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'source' => 'test',
        'reference' => 'test-premium',
        'unlocked_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/create-list')
        ->assertRedirect(route('onboarding.show', 'list'));
});

test('free users see locked cards after the first twenty five submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);
    $locked = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'content' => 'This locked dua should never render for a free owner.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Upgrade to unlock 1 more duas')
        ->assertSee('Locked dua request')
        ->assertDontSee('This locked dua should never render for a free owner.');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.complete', [$duaList, $locked]))
        ->assertForbidden();
});

test('premium users can view and manage all submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'content' => 'Premium owners can read this dua.',
    ]);
    UserEntitlement::query()->create([
        'user_id' => $owner->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'source' => 'test',
        'reference' => 'test-premium-visible',
        'unlocked_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Premium owners can read this dua.')
        ->assertDontSee('Locked dua request');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status->value)->toBe('completed');
});

test('checkout creates a pending Stripe payment and redirects to Stripe', function () {
    $user = User::factory()->create();

    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function createPremiumCheckout(User $user, ?string $successUrl = null, ?string $cancelUrl = null): array
        {
            return [
                'id' => 'cs_test_pending',
                'url' => 'https://checkout.stripe.test/session',
                'amount_total' => 1199,
                'currency' => 'usd',
            ];
        }
    });

    $this->actingAs($user)
        ->post(route('billing.checkout'))
        ->assertRedirect('https://checkout.stripe.test/session');

    $this->assertDatabaseHas('stripe_payments', [
        'user_id' => $user->id,
        'stripe_checkout_session_id' => 'cs_test_pending',
        'status' => StripePayment::STATUS_PENDING,
    ]);
});

test('billing success verifies ownership and unlocks premium', function () {
    $user = User::factory()->create();

    app()->instance(StripeCheckoutService::class, new class extends StripeCheckoutService
    {
        public function retrieveCheckoutSession(string $sessionId): mixed
        {
            return Session::constructFrom([
                'id' => $sessionId,
                'client_reference_id' => (string) auth()->id(),
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_success',
                'amount_total' => 1199,
                'currency' => 'usd',
                'metadata' => ['user_id' => (string) auth()->id()],
            ]);
        }
    });

    $this->actingAs($user)
        ->get(route('billing.success', ['session_id' => 'cs_test_success']))
        ->assertRedirect(route('dashboard.upgrade'))
        ->assertSessionHas('status', 'Premium unlocked successfully.');

    $this->assertDatabaseHas('user_entitlements', [
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'reference' => 'cs_test_success',
    ]);
});

test('stripe webhook verifies event and unlocks premium idempotently', function () {
    $user = User::factory()->create();

    app()->instance(StripeCheckoutService::class, new class($user) extends StripeCheckoutService
    {
        public function __construct(private readonly User $user) {}

        public function constructWebhookEvent(string $payload, string $signature): Event
        {
            return Event::constructFrom([
                'id' => 'evt_test_checkout',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_webhook',
                        'client_reference_id' => (string) $this->user->id,
                        'payment_status' => 'paid',
                        'payment_intent' => 'pi_test_webhook',
                        'amount_total' => 1199,
                        'currency' => 'usd',
                        'metadata' => ['user_id' => (string) $this->user->id],
                    ],
                ],
            ]);
        }
    });

    $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test-signature'])
        ->assertNoContent();

    $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test-signature'])
        ->assertNoContent();

    $this->assertDatabaseHas('stripe_payments', [
        'user_id' => $user->id,
        'stripe_checkout_session_id' => 'cs_test_webhook',
        'status' => StripePayment::STATUS_PAID,
    ]);

    expect(UserEntitlement::query()->where('user_id', $user->id)->where('key', UserEntitlement::KEY_PREMIUM)->count())->toBe(1);
});
