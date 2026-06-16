<?php

use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingProductCode;
use App\Enums\CommunityDuaStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function createForPurchase(BillingPurchase $purchase): array
        {
            return [
                'id' => 'pi_flow_'.$purchase->id,
                'client_secret' => 'pi_flow_'.$purchase->id.'_secret',
            ];
        }
    });
});

test('upgrade form starts embedded checkout for additional list', function () {
    $user = $this->actingAsUser();

    $this->post(route('billing.purchases.start'), [
        'product_code' => BillingProductCode::AdditionalList->value,
    ])->assertRedirect();

    $purchase = BillingPurchase::query()->where('user_id', $user->id)->latest('id')->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->product?->code)->toBe(BillingProductCode::AdditionalList->value);
});

test('upgrade form requires list for list scoped products', function () {
    $this->actingAsUser();

    $this->from(route('dashboard.upgrade'))
        ->post(route('billing.purchases.start'), [
            'product_code' => BillingProductCode::UnlimitedOneList->value,
        ])
        ->assertRedirect(route('dashboard.upgrade'))
        ->assertSessionHasErrors('dua_list_id');
});

test('upgrade form starts embedded checkout for request pack on owned list', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $this->post(route('billing.purchases.start'), [
        'product_code' => BillingProductCode::RequestPack25->value,
        'dua_list_id' => $duaList->id,
    ])->assertRedirect(route('billing.purchases.checkout', BillingPurchase::query()->latest('id')->first()));
});

test('upgrade page highlights product from query string', function () {
    $user = $this->actingAsUser();

    $this->get(route('dashboard.upgrade', [
        'product' => 'request_pack_25',
    ]))->assertOk()
        ->assertSee('name="product_code" value="REQUEST_PACK_25"', false);
});

test('community dua paid checkout starts embedded purchase flow', function () {
    $payload = [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara-paid-flow@example.com',
        'gender' => 'female',
        'content' => 'Please make dua for her family.',
        'terms' => '1',
    ];

    $this->post(route('community-dua.checkout'), $payload)
        ->assertRedirect();

    $purchase = BillingPurchase::query()->whereHas('communityDua', fn ($query) => $query->where('email', 'sara-paid-flow@example.com'))->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->product?->code)->toBe(BillingProductCode::CommunityDuaPaid->value);

    $this->assertDatabaseHas('community_duas', [
        'email' => 'sara-paid-flow@example.com',
        'status' => CommunityDuaStatus::PendingPayment->value,
        'is_visible' => false,
    ]);
});

test('community dua paid purchase fulfillment activates community dua', function () {
    $communityDua = CommunityDua::factory()->create([
        'status' => CommunityDuaStatus::PendingPayment,
        'is_visible' => false,
    ]);

    $product = BillingProduct::query()->where('code', BillingProductCode::CommunityDuaPaid->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => null,
        'community_dua_id' => $communityDua->id,
        'status' => \App\Enums\BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => null,
    ]);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    expect($communityDua->fresh())
        ->status->toBe(CommunityDuaStatus::Active)
        ->is_visible->toBeTrue()
        ->and($purchase->fresh()->fulfilled_at)->not->toBeNull();
});
