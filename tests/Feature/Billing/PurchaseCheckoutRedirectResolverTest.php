<?php

use App\Domains\Billing\Support\PurchaseCheckoutRedirectResolver;
use App\Enums\BillingProductCode;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

function redirectPurchase(BillingProductCode $productCode, User $user, ?DuaList $duaList = null): BillingPurchase
{
    $product = BillingProduct::query()->where('code', $productCode->value)->firstOrFail();

    return BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $duaList?->id,
    ])->load('product', 'duaList');
}

test('list scoped purchase success redirect uses dua list route key', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'slug' => 'noman-hajj-4',
    ]);

    $purchase = redirectPurchase(BillingProductCode::RequestPack25, $user, $duaList);
    $resolver = app(PurchaseCheckoutRedirectResolver::class);

    $url = $resolver->successUrl($purchase);

    expect($url)
        ->toBe(route('dashboard.lists.show', ['duaList' => $duaList, 'payment' => 'success']))
        ->toContain('/dashboard/lists/noman-hajj-4')
        ->not->toContain('/dashboard/lists/'.$duaList->id);
});

test('list scoped purchase failure redirect uses dua list route key', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'slug' => 'family-trip-2026',
    ]);

    $purchase = redirectPurchase(BillingProductCode::UnlimitedOneList, $user, $duaList);
    $resolver = app(PurchaseCheckoutRedirectResolver::class);

    expect($resolver->failureUrl($purchase))
        ->toBe(route('dashboard.lists.show', ['duaList' => $duaList]))
        ->toContain('/dashboard/lists/family-trip-2026');
});

test('account level purchase success redirect remains upgrade page', function () {
    $user = User::factory()->create();
    $purchase = redirectPurchase(BillingProductCode::AdditionalList, $user);
    $resolver = app(PurchaseCheckoutRedirectResolver::class);

    expect($resolver->successUrl($purchase))
        ->toBe(route('dashboard.upgrade', ['status' => 'paid']));
});

test('unlimited forever purchase success redirect remains upgrade page', function () {
    $user = User::factory()->create();
    $purchase = redirectPurchase(BillingProductCode::UnlimitedForever, $user);
    $resolver = app(PurchaseCheckoutRedirectResolver::class);

    expect($resolver->successUrl($purchase))
        ->toBe(route('dashboard.upgrade', ['status' => 'paid']));
});

test('account level purchase failure redirect remains upgrade page', function () {
    $user = User::factory()->create();
    $purchase = redirectPurchase(BillingProductCode::UnlimitedForever, $user);
    $resolver = app(PurchaseCheckoutRedirectResolver::class);

    expect($resolver->failureUrl($purchase))
        ->toBe(route('dashboard.upgrade'));
});
