<?php

use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Enums\CommunityDuaStatus;
use App\Enums\EntitlementKey;
use App\Enums\SubmissionLockReason;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Stripe\Event;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

/**
 * @return array{product: BillingProduct, purchase: BillingPurchase}
 */
function makeSucceededPurchase(BillingProductCode $productCode, User $user, ?DuaList $duaList = null): array
{
    $product = BillingProduct::query()->where('code', $productCode->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $user->id,
        'dua_list_id' => $duaList?->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'payment_intent_id' => 'pi_'.$productCode->value.'_'.fake()->unique()->numerify('####'),
        'fulfilled_at' => null,
    ]);

    return ['product' => $product, 'purchase' => $purchase];
}

function makeLockedSubmissions(DuaList $duaList, int $count): void
{
    DuaSubmission::factory()->count($count)->create([
        'dua_list_id' => $duaList->id,
        'is_locked' => true,
        'locked_at_quota' => (int) config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
    ]);
}

test('request pack fulfillment grants visible submissions and unlocks eligible submissions', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    makeLockedSubmissions($list, 30);

    ['purchase' => $purchase] = makeSucceededPurchase(BillingProductCode::RequestPack25, $user, $list);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    $purchase->refresh();

    expect($purchase->fulfilled_at)->not->toBeNull();

    $grant = EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->first();

    expect($grant)->not->toBeNull()
        ->and($grant->entitlement_key)->toBe(EntitlementKey::ListVisibleSubmissionPack)
        ->and($grant->quantity)->toBe((int) config('billing.request_pack_size'))
        ->and($grant->dua_list_id)->toBe($list->id);

    expect(DuaSubmission::query()
        ->where('dua_list_id', $list->id)
        ->where('unlock_purchase_id', $purchase->id)
        ->count())->toBe((int) config('billing.request_pack_unlock_batch'));

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveVisibleQuota($user, $list))
        ->toBe((int) config('billing.free_visible_submissions_per_list') + (int) config('billing.request_pack_size'));
});

test('unlimited one list fulfillment grants list override and unlocks all eligible submissions', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    makeLockedSubmissions($list, 12);

    ['purchase' => $purchase] = makeSucceededPurchase(BillingProductCode::UnlimitedOneList, $user, $list);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    $grant = EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->first();

    expect($grant?->entitlement_key)->toBe(EntitlementKey::ListUnlimitedOverride)
        ->and($grant?->dua_list_id)->toBe($list->id);

    expect(DuaSubmission::query()
        ->where('dua_list_id', $list->id)
        ->quotaLocked()
        ->count())->toBe(0);

    expect(DuaSubmission::query()
        ->where('dua_list_id', $list->id)
        ->where('unlock_purchase_id', $purchase->id)
        ->count())->toBe(12);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveVisibleQuota($user, $list))
        ->toBe((int) config('billing.unlimited_list_submission_cap'));
});

test('additional list fulfillment grants one extra list slot', function () {
    $user = User::factory()->create();

    ['purchase' => $purchase] = makeSucceededPurchase(BillingProductCode::AdditionalList, $user);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    $grant = EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->first();

    expect($grant?->entitlement_key)->toBe(EntitlementKey::UserExtraListSlot)
        ->and($grant?->quantity)->toBe(1);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveListCapacity($user))
        ->toBe((int) config('billing.default_list_capacity') + 1);
});

test('unlimited forever fulfillment grants user entitlement and unlocks all user lists', function () {
    $user = User::factory()->create();
    $firstList = DuaList::factory()->create(['user_id' => $user->id]);
    $secondList = DuaList::factory()->create(['user_id' => $user->id]);
    makeLockedSubmissions($firstList, 5);
    makeLockedSubmissions($secondList, 3);

    ['purchase' => $purchase] = makeSucceededPurchase(BillingProductCode::UnlimitedForever, $user);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    $grant = EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->first();

    expect($grant?->entitlement_key)->toBe(EntitlementKey::UserUnlimitedForever);

    expect(DuaSubmission::query()
        ->whereIn('dua_list_id', [$firstList->id, $secondList->id])
        ->where('unlock_purchase_id', $purchase->id)
        ->count())->toBe(8);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveListCapacity($user))->toBeNull()
        ->and($resolver->effectiveVisibleQuota($user, $firstList))
        ->toBe((int) config('billing.unlimited_list_submission_cap'));
});

test('fulfillment is idempotent for duplicate invocations', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    makeLockedSubmissions($list, 10);

    ['purchase' => $purchase] = makeSucceededPurchase(BillingProductCode::RequestPack25, $user, $list);
    $service = app(PurchaseFulfillmentService::class);

    $service->fulfill($purchase);
    $service->fulfill($purchase->fresh());

    expect(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->count())->toBe(1)
        ->and(DuaSubmission::query()->where('unlock_purchase_id', $purchase->id)->count())->toBe(10)
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::FulfillmentApplied)
            ->count())->toBe(1);
});

test('payment_intent.succeeded webhook triggers fulfillment once', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    makeLockedSubmissions($list, 8);

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => BillingProduct::query()->where('code', BillingProductCode::RequestPack25->value)->value('id'),
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'status' => BillingPurchaseStatus::RequiresPaymentMethod,
        'payment_intent_id' => 'pi_webhook_fulfill_001',
        'fulfilled_at' => null,
    ]);

    $eventPayload = [
        'id' => 'evt_fulfill_001',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_webhook_fulfill_001',
                'status' => 'succeeded',
            ],
        ],
    ];

    app()->instance(StripeCheckoutService::class, new class($eventPayload) extends StripeCheckoutService
    {
        /** @param array<string, mixed> $eventPayload */
        public function __construct(private readonly array $eventPayload) {}

        public function constructWebhookEvent(string $payload, string $signature): Event
        {
            return Event::constructFrom($this->eventPayload);
        }
    });

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    app()->instance(StripeCheckoutService::class, new class($eventPayload) extends StripeCheckoutService
    {
        /** @param array<string, mixed> $eventPayload */
        public function __construct(private readonly array $eventPayload) {}

        public function constructWebhookEvent(string $payload, string $signature): Event
        {
            return Event::constructFrom(array_merge($this->eventPayload, ['id' => 'evt_fulfill_001_duplicate']));
        }
    });

    $this->postJson(route('api.v1.billing.webhooks.stripe'), [], [
        'Stripe-Signature' => 'test-signature',
    ])->assertNoContent();

    $purchase->refresh();

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded)
        ->and($purchase->fulfilled_at)->not->toBeNull()
        ->and(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->count())->toBe(1)
        ->and(DuaSubmission::query()->where('unlock_purchase_id', $purchase->id)->count())->toBe(8);
});

test('community dua purchases activate community dua on fulfillment', function () {
    $communityDua = CommunityDua::factory()->create([
        'status' => CommunityDuaStatus::PendingPayment,
        'is_visible' => false,
    ]);
    $product = BillingProduct::query()->where('code', BillingProductCode::CommunityDuaPaid->value)->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => null,
        'community_dua_id' => $communityDua->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => null,
    ]);

    app(PurchaseFulfillmentService::class)->fulfill($purchase);

    expect($purchase->fresh()->fulfilled_at)->not->toBeNull()
        ->and($communityDua->fresh()->status)->toBe(CommunityDuaStatus::Active)
        ->and($communityDua->fresh()->is_visible)->toBeTrue()
        ->and(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->exists())->toBeFalse();
});
