<?php

use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function retrieve(string $paymentIntentId): array
        {
            return [
                'id' => $paymentIntentId,
                'client_secret' => $paymentIntentId.'_secret_test',
            ];
        }
    });
});

function purchaseForProduct(string $productCode, array $overrides = []): BillingPurchase
{
    $product = BillingProduct::query()->where('code', $productCode)->firstOrFail();

    return BillingPurchase::factory()->create(array_merge([
        'billing_product_id' => $product->id,
        'payment_intent_id' => 'pi_test_'.fake()->unique()->numerify('######'),
    ], $overrides));
}

test('owner can retrieve purchase checkout details', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertOk()
        ->assertJsonPath('data.id', $purchase->id)
        ->assertJsonPath('data.product_code', BillingProductCode::UnlimitedForever->value)
        ->assertJsonPath('data.is_payable', true)
        ->assertJsonPath('data.is_completed', false);
});

test('list owner can retrieve list scoped purchase details', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $purchase = purchaseForProduct(BillingProductCode::RequestPack25->value, [
        'user_id' => $user->id,
        'dua_list_id' => $duaList->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertOk()
        ->assertJsonPath('data.dua_list_id', $duaList->id);
});

test('non owner cannot retrieve purchase details', function () {
    $owner = User::factory()->create();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $owner->id,
    ]);

    $this->actingAsUser();

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertForbidden()
        ->assertJsonPath('error_code', 'purchase_access_denied');
});

test('guest cannot retrieve authenticated product purchase details', function () {
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => User::factory()->create()->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'authentication_required');
});

test('guest can retrieve community dua purchase details', function () {
    $communityDua = CommunityDua::factory()->create();
    $purchase = purchaseForProduct(BillingProductCode::CommunityDuaPaid->value, [
        'user_id' => null,
        'community_dua_id' => $communityDua->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertOk()
        ->assertJsonPath('data.product_code', BillingProductCode::CommunityDuaPaid->value)
        ->assertJsonPath('data.community_dua_id', $communityDua->id);
});

test('owner can retrieve client secret for payable purchase', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::AdditionalList->value, [
        'user_id' => $user->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.client-secret', $purchase))
        ->assertOk()
        ->assertJsonPath('data.payment_intent_id', $purchase->payment_intent_id)
        ->assertJsonPath('data.client_secret', $purchase->payment_intent_id.'_secret_test');
});

test('client secret endpoint does not create a new payment intent', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::AdditionalList->value, [
        'user_id' => $user->id,
    ]);

    $createSpy = Mockery::spy(StripePaymentIntentService::class);
    $createSpy->shouldReceive('retrieve')->andReturn([
        'id' => $purchase->payment_intent_id,
        'client_secret' => 'existing_secret',
    ]);
    app()->instance(StripePaymentIntentService::class, $createSpy);

    $this->getJson(route('api.v1.billing.purchases.client-secret', $purchase))
        ->assertOk()
        ->assertJsonPath('data.client_secret', 'existing_secret');

    $createSpy->shouldNotHaveReceived('createForPurchase');
});

test('client secret endpoint rejects completed purchase', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::AdditionalList->value, [
        'user_id' => $user->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => now(),
    ]);

    $this->getJson(route('api.v1.billing.purchases.client-secret', $purchase))
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'purchase_not_payable');
});

test('guest can retrieve client secret for community dua purchase', function () {
    $communityDua = CommunityDua::factory()->create();
    $purchase = purchaseForProduct(BillingProductCode::CommunityDuaPaid->value, [
        'user_id' => null,
        'community_dua_id' => $communityDua->id,
    ]);

    $this->getJson(route('api.v1.billing.purchases.client-secret', $purchase))
        ->assertOk()
        ->assertJsonPath('data.client_secret', fn (?string $value) => $value !== null);
});

test('payment status endpoint returns purchase state', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedOneList->value, [
        'user_id' => $user->id,
        'dua_list_id' => DuaList::factory()->create(['user_id' => $user->id])->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => now(),
    ]);

    $this->getJson(route('api.v1.billing.purchases.payment-status', $purchase))
        ->assertOk()
        ->assertJsonPath('data.status', BillingPurchaseStatus::Succeeded->value)
        ->assertJsonPath('data.is_completed', true)
        ->assertJsonPath('data.is_payable', false);
});

test('succeeded purchase details show completed state for refresh', function () {
    $user = $this->actingAsUser();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => now(),
    ]);

    $this->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertOk()
        ->assertJsonPath('data.is_completed', true)
        ->assertJsonPath('data.is_payable', false);
});

test('web session authenticates purchase api requests without bearer token', function () {
    $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1', $host],
    ]);

    $this->withMiddleware(EnsureFrontendRequestsAreStateful::class);

    $user = User::factory()->create();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->withHeader('Origin', (string) config('app.url'))
        ->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertOk()
        ->assertJsonPath('data.user_id', $user->id);
});

test('web session cannot access another users purchase', function () {
    $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

    config([
        'sanctum.stateful' => ['localhost', '127.0.0.1', $host],
    ]);

    $this->withMiddleware(EnsureFrontendRequestsAreStateful::class);

    $owner = User::factory()->create();
    $purchase = purchaseForProduct(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $owner->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->withHeader('Origin', (string) config('app.url'))
        ->getJson(route('api.v1.billing.purchases.show', $purchase))
        ->assertForbidden()
        ->assertJsonPath('error_code', 'purchase_access_denied');
});
