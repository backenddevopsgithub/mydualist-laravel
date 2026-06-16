<?php

use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        /** @var array<int, array<string, mixed>> */
        public array $createPayloads = [];

        /** @var array<int, string> */
        public array $retrievePayloads = [];

        public function createForPurchase(BillingPurchase $purchase): array
        {
            $this->createPayloads[] = [
                'purchase_id' => $purchase->id,
                'idempotency_key' => 'purchase:'.$purchase->idempotency_key,
                'amount' => $purchase->amount_minor,
                'currency' => $purchase->currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'billing_purchase_id' => (string) $purchase->id,
                    'billing_product_code' => (string) optional($purchase->product)->code,
                    'billing_user_id' => $purchase->user_id ? (string) $purchase->user_id : '',
                    'billing_dua_list_id' => $purchase->dua_list_id ? (string) $purchase->dua_list_id : '',
                    'billing_community_dua_id' => $purchase->community_dua_id ? (string) $purchase->community_dua_id : '',
                    'billing_idempotency_key' => $purchase->idempotency_key,
                ],
            ];

            return [
                'id' => 'pi_'.$purchase->id,
                'client_secret' => 'pi_'.$purchase->id.'_secret_test',
            ];
        }

        public function retrieve(string $paymentIntentId): array
        {
            $this->retrievePayloads[] = $paymentIntentId;

            return [
                'id' => $paymentIntentId,
                'client_secret' => $paymentIntentId.'_secret_test',
            ];
        }
    });
});

test('authenticated user can create list scoped purchase', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'REQUEST_PACK_25',
        'idempotency_key' => 'idem-list-001',
        'dua_list_id' => $duaList->id,
    ])->assertCreated()
        ->assertJsonPath('data.product_code', 'REQUEST_PACK_25')
        ->assertJsonPath('data.status', BillingPurchaseStatus::RequiresPaymentMethod->value)
        ->assertJsonPath('data.dua_list_id', $duaList->id)
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.payment_intent_id', fn (string $value) => str_starts_with($value, 'pi_'))
        ->assertJsonPath('data.client_secret', fn (string $value) => str_contains($value, '_secret_'));

    $this->assertDatabaseHas('billing_purchases', [
        'user_id' => $user->id,
        'dua_list_id' => $duaList->id,
        'idempotency_key' => 'idem-list-001',
    ]);
});

test('idempotent purchase creation returns existing row', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $payload = [
        'product_code' => 'REQUEST_PACK_25',
        'idempotency_key' => 'idem-list-002',
        'dua_list_id' => $duaList->id,
    ];

    $first = $this->postJson('/api/v1/billing/purchases', $payload)
        ->assertCreated()
        ->json('data.id');

    $this->postJson('/api/v1/billing/purchases', $payload)
        ->assertOk()
        ->assertJsonPath('data.id', $first)
        ->assertJsonPath('data.client_secret', fn (?string $value) => $value !== null);

    expect(BillingPurchase::query()->where('idempotency_key', 'idem-list-002')->count())->toBe(1);
});

test('idempotency key conflict returns 409', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'REQUEST_PACK_25',
        'idempotency_key' => 'idem-conflict-001',
        'dua_list_id' => $duaList->id,
    ])->assertCreated();

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'UNLIMITED_FOREVER',
        'idempotency_key' => 'idem-conflict-001',
    ])->assertStatus(409)
        ->assertJsonPath('error_code', 'idempotency_conflict');
});

test('authenticated product rejects guest purchase', function () {
    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'UNLIMITED_FOREVER',
        'idempotency_key' => 'idem-auth-001',
    ])->assertStatus(401)
        ->assertJsonPath('error_code', 'authentication_required');
});

test('list scoped product requires owned list', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAsUser();

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'REQUEST_PACK_25',
        'idempotency_key' => 'idem-list-owner-001',
        'dua_list_id' => $duaList->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['dua_list_id']);
});

test('community dua product can be created without authentication', function () {
    $communityDua = CommunityDua::factory()->create();

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'COMMUNITY_DUA_PAID',
        'idempotency_key' => 'idem-community-001',
        'community_dua_id' => $communityDua->id,
    ])->assertCreated()
        ->assertJsonPath('data.product_code', 'COMMUNITY_DUA_PAID')
        ->assertJsonPath('data.community_dua_id', $communityDua->id)
        ->assertJsonPath('data.user_id', null);
});

test('bearer token resolves authenticated user on purchase route', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('purchase-test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/billing/purchases', [
            'product_code' => 'REQUEST_PACK_25',
            'idempotency_key' => 'idem-bearer-001',
            'dua_list_id' => $duaList->id,
        ])->assertCreated()
        ->assertJsonPath('data.user_id', $user->id);
});

test('payment intent request uses expected idempotency and metadata contract', function () {
    $user = $this->actingAsUser();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/billing/purchases', [
        'product_code' => 'REQUEST_PACK_25',
        'idempotency_key' => 'idem-contract-001',
        'dua_list_id' => $duaList->id,
    ])->assertCreated();

    /** @var StripePaymentIntentService&object{createPayloads: array<int, array<string, mixed>>} $service */
    $service = app(StripePaymentIntentService::class);
    $payload = $service->createPayloads[0] ?? null;

    expect($payload)->not->toBeNull()
        ->and($payload['idempotency_key'])->toBe('purchase:idem-contract-001')
        ->and($payload['amount'])->toBe(200)
        ->and($payload['currency'])->toBe('gbp')
        ->and($payload['automatic_payment_methods'])->toMatchArray([
            'enabled' => true,
            'allow_redirects' => 'never',
        ])
        ->and($payload['metadata'])->toMatchArray([
            'billing_product_code' => 'REQUEST_PACK_25',
            'billing_user_id' => (string) $user->id,
            'billing_dua_list_id' => (string) $duaList->id,
            'billing_community_dua_id' => '',
            'billing_idempotency_key' => 'idem-contract-001',
        ]);
});
