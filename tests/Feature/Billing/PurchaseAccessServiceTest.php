<?php

use App\Domains\Billing\Services\PurchaseAccessService;
use App\Enums\BillingProductCode;
use App\Enums\BillingProductScope;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

test('authenticated user can access own user scoped purchase', function () {
    $user = User::factory()->create();
    $product = BillingProduct::factory()->create([
        'code' => BillingProductCode::UnlimitedForever->value,
        'scope' => BillingProductScope::User,
        'requires_authentication' => true,
    ]);
    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    app(PurchaseAccessService::class)->assertAccessible($purchase, $user);

    expect(true)->toBeTrue();
});

test('list owner can access list scoped purchase', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $product = BillingProduct::factory()->create([
        'code' => BillingProductCode::RequestPack25->value,
        'scope' => BillingProductScope::List,
        'requires_authentication' => true,
    ]);
    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $duaList->id,
    ]);

    app(PurchaseAccessService::class)->assertAccessible($purchase, $user);

    expect(true)->toBeTrue();
});

test('guest can access community dua purchase without owner', function () {
    $product = BillingProduct::factory()->create([
        'code' => BillingProductCode::CommunityDuaPaid->value,
        'scope' => BillingProductScope::CommunityDua,
        'requires_authentication' => false,
    ]);
    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => null,
        'community_dua_id' => CommunityDua::factory()->create()->id,
    ]);

    app(PurchaseAccessService::class)->assertAccessible($purchase, null);

    expect(true)->toBeTrue();
});

test('guest cannot access authenticated product purchase', function () {
    $product = BillingProduct::factory()->create([
        'requires_authentication' => true,
    ]);
    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
    ]);

    app(PurchaseAccessService::class)->assertAccessible($purchase, null);
})->throws(AuthenticationException::class);

test('non owner cannot access another users purchase', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = BillingProduct::factory()->create([
        'requires_authentication' => true,
    ]);
    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $owner->id,
    ]);

    app(PurchaseAccessService::class)->assertAccessible($purchase, $otherUser);
})->throws(AuthorizationException::class);
