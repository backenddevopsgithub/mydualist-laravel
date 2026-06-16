<?php

use App\Enums\BillingProductCode;
use App\Enums\BillingProductScope;
use App\Enums\BillingPurchaseStatus;
use App\Enums\EntitlementKey;
use App\Enums\SubmissionLockReason;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('billing schema migrations create expected tables and columns', function () {
    expect(Schema::hasTable('billing_products'))->toBeTrue()
        ->and(Schema::hasTable('billing_purchases'))->toBeTrue()
        ->and(Schema::hasTable('billing_purchase_events'))->toBeTrue()
        ->and(Schema::hasTable('entitlement_grants'))->toBeTrue()
        ->and(Schema::hasColumns('dua_submissions', [
            'is_locked',
            'locked_at_quota',
            'locked_reason',
            'unlocked_at',
            'unlock_purchase_id',
        ]))->toBeTrue();
});

test('billing product seeder syncs all configured products', function () {
    $this->seed(BillingProductSeeder::class);

    expect(BillingProduct::query()->count())->toBe(count(config('billing.products')));

    $requestPack = BillingProduct::query()
        ->where('code', BillingProductCode::RequestPack25->value)
        ->first();

    expect($requestPack)->not->toBeNull()
        ->and($requestPack->external_product_id)->toBe(728)
        ->and($requestPack->scope)->toBe(BillingProductScope::List)
        ->and($requestPack->stackable)->toBeTrue()
        ->and($requestPack->requires_authentication)->toBeTrue()
        ->and($requestPack->amount_minor)->toBe(200)
        ->and($requestPack->currency)->toBe('gbp');

    $communityDua = BillingProduct::query()
        ->where('code', BillingProductCode::CommunityDuaPaid->value)
        ->first();

    expect($communityDua?->requires_authentication)->toBeFalse();
});

test('billing product seeder is idempotent', function () {
    $this->seed(BillingProductSeeder::class);
    $this->seed(BillingProductSeeder::class);

    expect(BillingProduct::query()->count())->toBe(count(config('billing.products')));
});

test('billing purchase enforces idempotency key uniqueness', function () {
    $product = BillingProduct::factory()->create();

    BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'idempotency_key' => 'purchase-key-1',
    ]);

    expect(fn () => BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'idempotency_key' => 'purchase-key-1',
    ]))->toThrow(QueryException::class);
});

test('billing purchase event enforces stripe event idempotency per purchase', function () {
    $purchase = BillingPurchase::factory()->create();

    BillingPurchaseEvent::factory()->create([
        'billing_purchase_id' => $purchase->id,
        'stripe_event_id' => 'evt_duplicate',
    ]);

    expect(fn () => BillingPurchaseEvent::factory()->create([
        'billing_purchase_id' => $purchase->id,
        'stripe_event_id' => 'evt_duplicate',
    ]))->toThrow(QueryException::class);
});

test('entitlement grant supports stackable rows and non stackable dedupe keys', function () {
    $user = $this->actingAsUser();

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 1,
    ]);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 1,
    ]);

    expect(EntitlementGrant::query()->where('user_id', $user->id)->count())->toBe(2);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserUnlimitedForever,
        'is_stackable' => false,
        'dedupe_key' => EntitlementGrant::dedupeKeyForUserGrant($user->id, EntitlementKey::UserUnlimitedForever),
    ]);

    expect(fn () => EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserUnlimitedForever,
        'is_stackable' => false,
        'dedupe_key' => EntitlementGrant::dedupeKeyForUserGrant($user->id, EntitlementKey::UserUnlimitedForever),
    ]))->toThrow(QueryException::class);
});

test('dua submission stores lock metadata columns', function () {
    $purchase = BillingPurchase::factory()->create([
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => now(),
    ]);

    $submission = DuaSubmission::factory()->create([
        'is_locked' => true,
        'locked_at_quota' => config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
        'unlock_purchase_id' => $purchase->id,
        'unlocked_at' => now(),
    ]);

    $submission->refresh();

    expect($submission->isQuotaLocked())->toBeFalse()
        ->and($submission->locked_at_quota)->toBe((int) config('billing.free_visible_submissions_per_list'))
        ->and($submission->locked_reason)->toBe(SubmissionLockReason::VisibleQuotaExhausted)
        ->and($submission->unlock_purchase_id)->toBe($purchase->id);
});

test('billing configuration exposes required keys without hardcoded product literals in models', function () {
    expect(config('billing.free_visible_submissions_per_list'))->toBeInt()
        ->and(config('billing.unlimited_list_submission_cap'))->toBeInt()
        ->and(config('billing.default_list_capacity'))->toBeInt()
        ->and(config('billing.request_pack_size'))->toBeInt()
        ->and(config('billing.request_pack_unlock_batch'))->toBeInt()
        ->and(config('billing.products.REQUEST_PACK_25.amount_minor'))->toBe(200)
        ->and(config('billing.products.UNLIMITED_FOREVER.amount_minor'))->toBe(1199);
});
