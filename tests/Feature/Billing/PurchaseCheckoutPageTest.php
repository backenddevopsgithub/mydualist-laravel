<?php

use App\Enums\BillingProductCode;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(BillingProductSeeder::class);
});

function checkoutPurchase(string $productCode, array $overrides = []): BillingPurchase
{
    $product = BillingProduct::query()->where('code', $productCode)->firstOrFail();

    return BillingPurchase::factory()->create(array_merge([
        'billing_product_id' => $product->id,
        'payment_intent_id' => 'pi_page_test',
    ], $overrides));
}

test('authenticated owner can view checkout page', function () {
    config(['services.stripe.key' => 'pk_test_checkout']);

    $user = $this->actingAsUser();
    $purchase = checkoutPurchase(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
    ]);

    $this->get(route('billing.purchases.checkout', $purchase))
        ->assertOk()
        ->assertSee('id="billing-checkout-root"', false)
        ->assertSee('data-purchase-id="'.$purchase->id.'"', false)
        ->assertSee('data-stripe-key="pk_test_checkout"', false)
        ->assertSee('data-success-url=', false);
});

test('guest can view community dua checkout page', function () {
    config(['services.stripe.key' => 'pk_test_guest']);

    $communityDua = CommunityDua::factory()->create();
    $purchase = checkoutPurchase(BillingProductCode::CommunityDuaPaid->value, [
        'user_id' => null,
        'community_dua_id' => $communityDua->id,
    ]);

    $this->get(route('billing.purchases.checkout', $purchase))
        ->assertOk()
        ->assertSee('Complete payment', false);
});

test('non owner is denied checkout page', function () {
    config(['services.stripe.key' => 'pk_test_checkout']);

    $owner = User::factory()->create();
    $purchase = checkoutPurchase(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $owner->id,
    ]);

    $this->actingAsUser();

    $this->get(route('billing.purchases.checkout', $purchase))
        ->assertForbidden();
});

test('guest is denied authenticated product checkout page', function () {
    config(['services.stripe.key' => 'pk_test_checkout']);

    $purchase = checkoutPurchase(BillingProductCode::UnlimitedForever->value, [
        'user_id' => User::factory()->create()->id,
    ]);

    $this->get(route('billing.purchases.checkout', $purchase))
        ->assertUnauthorized();
});

test('checkout page is unavailable without stripe key', function () {
    config(['services.stripe.key' => null]);

    $user = User::factory()->create();
    $purchase = checkoutPurchase(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $this->get(route('billing.purchases.checkout', $purchase))
        ->assertStatus(503);
});
