<?php

use App\Domains\Billing\Actions\MarkBillingPurchaseFulfilledAction;
use App\Domains\Billing\Actions\MarkBillingPurchaseRefundedAction;
use App\Domains\Billing\Actions\RefundBillingPurchaseViaStripeAction;
use App\Domains\Billing\Actions\RetryBillingPurchaseFulfillmentAction;
use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Filament\Resources\BillingPurchaseResource\Pages\ListBillingPurchases;
use App\Filament\Resources\BillingPurchaseResource\Pages\ViewBillingPurchase;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\StripePayment;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

function makeAdminBillingPurchase(array $attributes = []): BillingPurchase
{
    $user = User::factory()->create();
    $product = BillingProduct::query()->where('code', BillingProductCode::AdditionalList->value)->firstOrFail();

    return BillingPurchase::factory()->create(array_merge([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'payment_intent_id' => 'pi_test_'.fake()->unique()->numerify('######'),
        'fulfilled_at' => null,
        'amount_minor' => 799,
        'currency' => 'gbp',
    ], $attributes));
}

test('billing purchases resource is restricted to active admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->get('/admin/billing-purchases')->assertRedirect('/admin/login');

    $this->actingAs($user)
        ->get('/admin/billing-purchases')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/billing-purchases')
        ->assertOk();
});

test('billing purchases list shows purchase records for admins', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase();

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->assertCanSeeTableRecords([$purchase]);
});

test('billing purchases table can filter by status', function () {
    $admin = User::factory()->admin()->create();

    $succeeded = makeAdminBillingPurchase();
    $processing = makeAdminBillingPurchase([
        'status' => BillingPurchaseStatus::Processing,
        'payment_intent_id' => 'pi_processing_'.fake()->unique()->numerify('######'),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->filterTable('status', BillingPurchaseStatus::Succeeded->value)
        ->assertCanSeeTableRecords([$succeeded])
        ->assertCanNotSeeTableRecords([$processing]);
});

test('billing purchases table can filter by provider product fulfillment and date', function () {
    $admin = User::factory()->admin()->create();
    $additionalList = BillingProduct::query()->where('code', BillingProductCode::AdditionalList->value)->firstOrFail();
    $requestPack = BillingProduct::query()->where('code', BillingProductCode::RequestPack25->value)->firstOrFail();

    $stripePurchase = makeAdminBillingPurchase([
        'billing_product_id' => $additionalList->id,
        'created_at' => now()->subDay(),
    ]);

    $woocommercePurchase = makeAdminBillingPurchase([
        'billing_product_id' => $requestPack->id,
        'wp_order_id' => 9001,
        'payment_intent_id' => null,
        'created_at' => now()->subDay(),
    ]);

    $oldFulfilledPurchase = makeAdminBillingPurchase([
        'billing_product_id' => $additionalList->id,
        'fulfilled_at' => now(),
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->filterTable('provider', 'woocommerce')
        ->assertCanSeeTableRecords([$woocommercePurchase])
        ->assertCanNotSeeTableRecords([$stripePurchase]);

    Livewire::test(ListBillingPurchases::class)
        ->filterTable('billing_product_id', (string) $additionalList->id)
        ->assertCanSeeTableRecords([$stripePurchase, $oldFulfilledPurchase])
        ->assertCanNotSeeTableRecords([$woocommercePurchase]);

    Livewire::test(ListBillingPurchases::class)
        ->filterTable('fulfilled', false)
        ->assertCanSeeTableRecords([$stripePurchase, $woocommercePurchase])
        ->assertCanNotSeeTableRecords([$oldFulfilledPurchase]);

    Livewire::test(ListBillingPurchases::class)
        ->filterTable('created_between', [
            'from' => now()->subDays(2)->toDateString(),
            'until' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$stripePurchase, $woocommercePurchase])
        ->assertCanNotSeeTableRecords([$oldFulfilledPurchase]);
});

test('admin can retry fulfillment for an unfulfilled succeeded purchase', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase();

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->callTableAction('retryFulfillment', $purchase)
        ->assertNotified();

    expect($purchase->fresh()->fulfilled_at)->not->toBeNull()
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::FulfillmentApplied)
            ->exists())->toBeTrue();
});

test('admin can mark a succeeded purchase as fulfilled without rerunning handlers', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase();

    $fulfillment = Mockery::spy(PurchaseFulfillmentService::class);
    app()->instance(PurchaseFulfillmentService::class, $fulfillment);

    $this->actingAs($admin);

    app(MarkBillingPurchaseFulfilledAction::class)($purchase, $admin);

    $fulfillment->shouldNotHaveReceived('fulfill');

    expect($purchase->fresh()->fulfilled_at)->not->toBeNull()
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::AdminMarkedFulfilled)
            ->exists())->toBeTrue();
});

test('admin can mark a purchase as refunded locally from the table', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase(['fulfilled_at' => now()]);

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->callTableAction('markRefunded', $purchase)
        ->assertNotified();

    expect($purchase->fresh()->refunded_at)->not->toBeNull()
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::AdminMarkedRefunded)
            ->exists())->toBeTrue();
});

test('admin can refund a stripe purchase via stripe action', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase(['fulfilled_at' => now()]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function refundPaymentIntent(string $paymentIntentId, ?int $amountMinor = null): array
        {
            return [
                'id' => 're_test_'.fake()->unique()->numerify('######'),
                'amount' => $amountMinor ?? 799,
                'status' => 'succeeded',
            ];
        }
    });

    $this->actingAs($admin);

    Livewire::test(ListBillingPurchases::class)
        ->callTableAction('refundViaStripe', $purchase)
        ->assertNotified();

    expect($purchase->fresh()->refunded_at)->not->toBeNull()
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::AdminStripeRefund)
            ->exists())->toBeTrue();
});

test('billing purchase view page links to stripe payment and shows entitlement grants relation', function () {
    $admin = User::factory()->admin()->create();
    $purchase = makeAdminBillingPurchase();

    $stripePayment = StripePayment::query()->create([
        'user_id' => $purchase->user_id,
        'stripe_checkout_session_id' => 'cs_test_'.$purchase->id,
        'stripe_payment_intent_id' => $purchase->payment_intent_id,
        'amount_total' => $purchase->amount_minor,
        'currency' => $purchase->currency,
        'status' => StripePayment::STATUS_PAID,
        'paid_at' => now(),
    ]);

    app(RetryBillingPurchaseFulfillmentAction::class)($purchase->fresh(['product', 'user']));

    $this->actingAs($admin);

    Livewire::test(ViewBillingPurchase::class, ['record' => $purchase->getRouteKey()])
        ->assertOk()
        ->assertSee('Payment #'.$stripePayment->id)
        ->assertSee('grant(s)');
});

test('non-admin cannot invoke billing purchase update actions through policy', function () {
    $user = User::factory()->create();
    $purchase = makeAdminBillingPurchase();

    expect($user->can('viewAny', BillingPurchase::class))->toBeFalse()
        ->and($user->can('view', $purchase))->toBeFalse()
        ->and($user->can('update', $purchase))->toBeFalse();
});
