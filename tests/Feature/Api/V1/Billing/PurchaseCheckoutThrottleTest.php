<?php

use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
    RateLimiter::clearResolvedInstances();

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function createForPurchase(BillingPurchase $purchase): array
        {
            return [
                'id' => 'pi_'.$purchase->id,
                'client_secret' => 'pi_'.$purchase->id.'_secret_test',
            ];
        }
    });
});

function throttlePurchase(string $productCode, array $overrides = []): BillingPurchase
{
    $product = BillingProduct::query()->where('code', $productCode)->firstOrFail();

    return BillingPurchase::factory()->create(array_merge([
        'billing_product_id' => $product->id,
        'payment_intent_id' => 'pi_throttle_test',
    ], $overrides));
}

test('purchase store remains strictly throttled at six requests per minute', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $payload = [
        'product_code' => BillingProductCode::RequestPack25->value,
        'idempotency_key' => 'throttle-store-',
        'dua_list_id' => $duaList->id,
    ];

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $payload['idempotency_key'] = 'throttle-store-'.$attempt;

        $this->postJson(route('api.v1.billing.purchases.store'), $payload)
            ->assertCreated();
    }

    $payload['idempotency_key'] = 'throttle-store-7';

    $this->postJson(route('api.v1.billing.purchases.store'), $payload)
        ->assertTooManyRequests();
});

test('checkout read endpoints tolerate payment status polling within one minute', function () {
    $user = $this->actingAsUser();
    $purchase = throttlePurchase(BillingProductCode::UnlimitedForever->value, [
        'user_id' => $user->id,
        'status' => BillingPurchaseStatus::Processing,
    ]);

    for ($attempt = 0; $attempt < 20; $attempt++) {
        $this->getJson(route('api.v1.billing.purchases.payment-status', $purchase))
            ->assertOk()
            ->assertJsonPath('data.status', BillingPurchaseStatus::Processing->value);
    }
});

test('checkout read endpoints allow repeated purchase detail and client secret requests', function () {
    $user = $this->actingAsUser();
    $purchase = throttlePurchase(BillingProductCode::AdditionalList->value, [
        'user_id' => $user->id,
    ]);

    app()->instance(\App\Domains\Billing\Services\StripePaymentIntentService::class, new class extends \App\Domains\Billing\Services\StripePaymentIntentService
    {
        public function retrieve(string $paymentIntentId): array
        {
            return [
                'id' => $paymentIntentId,
                'client_secret' => $paymentIntentId.'_secret_test',
            ];
        }
    });

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->getJson(route('api.v1.billing.purchases.show', $purchase))->assertOk();
        $this->getJson(route('api.v1.billing.purchases.client-secret', $purchase))->assertOk();
    }
});

test('legacy billing write routes remain on the strict billing throttle', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->actingAs($user)
            ->post(route('billing.checkout'))
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post(route('billing.checkout'))
        ->assertTooManyRequests();
});
