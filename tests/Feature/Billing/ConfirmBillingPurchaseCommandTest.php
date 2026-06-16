<?php

use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseStatus;
use App\Enums\EntitlementKey;
use App\Enums\SubmissionLockReason;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Stripe\PaymentIntent;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

test('billing confirm purchase command confirms stripe pi and processes fulfillment locally', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $product = BillingProduct::query()->where('code', BillingProductCode::RequestPack25->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
        'payment_intent_id' => 'pi_dev_confirm_test',
        'fulfilled_at' => null,
    ]);

    DuaSubmission::factory()->count(3)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'locked_at_quota' => (int) config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieveIntent(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'requires_payment_method',
                'amount' => 200,
                'currency' => 'gbp',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
            ]);
        }

        public function confirmInTestMode(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'succeeded',
                'amount' => 200,
                'currency' => 'gbp',
            ]);
        }
    });

    $this->artisan('billing:confirm-purchase', [
        '--purchase' => $purchase->id,
    ])->assertSuccessful();

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded)
        ->and($purchase->fulfilled_at)->not->toBeNull();

    expect(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->count())->toBe(1)
        ->and(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->value('entitlement_key'))
        ->toBe(EntitlementKey::ListVisibleSubmissionPack)
        ->and(DuaSubmission::query()->where('unlock_purchase_id', $purchase->id)->count())->toBe(3);
});

test('billing confirm purchase command is blocked outside local and testing', function () {
    $original = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');

        $this->artisan('billing:confirm-purchase', [
            '--purchase' => 1,
        ])->assertFailed();
    } finally {
        app()->detectEnvironment(fn () => $original);
    }
});

test('billing confirm purchase command rejects legacy redirect payment intents without local-only', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $product = BillingProduct::query()->where('code', BillingProductCode::RequestPack25->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
        'payment_intent_id' => 'pi_legacy_redirect',
        'fulfilled_at' => null,
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieveIntent(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'requires_payment_method',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'always',
                ],
            ]);
        }
    });

    $this->artisan('billing:confirm-purchase', [
        '--purchase' => $purchase->id,
    ])->assertFailed();
});

test('billing confirm purchase command can simulate fulfillment for legacy payment intents with local-only', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $product = BillingProduct::query()->where('code', BillingProductCode::RequestPack25->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
        'payment_intent_id' => 'pi_legacy_local_only',
        'fulfilled_at' => null,
    ]);

    DuaSubmission::factory()->count(2)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'locked_at_quota' => (int) config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieveIntent(string $paymentIntentId): PaymentIntent
        {
            return PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'status' => 'requires_payment_method',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'always',
                ],
            ]);
        }
    });

    $this->artisan('billing:confirm-purchase', [
        '--purchase' => $purchase->id,
        '--local-only' => true,
    ])->assertSuccessful();

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded)
        ->and($purchase->fulfilled_at)->not->toBeNull()
        ->and(DuaSubmission::query()->where('unlock_purchase_id', $purchase->id)->count())->toBe(2);
});
