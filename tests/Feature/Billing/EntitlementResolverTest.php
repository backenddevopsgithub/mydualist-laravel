<?php

use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Enums\EntitlementKey;
use App\Enums\SubmissionLockReason;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Models\UserEntitlement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('free user has default list capacity and visible quota from config', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $service = app(UserEntitlementService::class);

    expect($service->activeListLimit($user))->toBe((int) config('billing.default_list_capacity'))
        ->and($service->canCreateList($user))->toBeTrue()
        ->and($service->visibleSubmissionLimit($user, $list))->toBe((int) config('billing.free_visible_submissions_per_list'));
});

test('additional list grants increase effective list capacity', function () {
    $user = User::factory()->create();
    DuaList::factory()->count(2)->create(['user_id' => $user->id]);

    EntitlementGrant::factory()->count(2)->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 1,
    ]);

    $service = app(UserEntitlementService::class);

    expect($service->activeListLimit($user))->toBe((int) config('billing.default_list_capacity') + 2)
        ->and($service->canCreateList($user))->toBeTrue()
        ->and($user->entitlementQuantity(EntitlementKey::UserExtraListSlot))->toBe(2);
});

test('unlimited forever grant removes list capacity limit and raises visible quota', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserUnlimitedForever,
        'is_stackable' => false,
        'dedupe_key' => EntitlementGrant::dedupeKeyForUserGrant($user->id, EntitlementKey::UserUnlimitedForever),
    ]);

    $service = app(UserEntitlementService::class);
    $resolver = app(EntitlementResolverService::class);

    expect($service->activeListLimit($user))->toBeNull()
        ->and($service->hasPremium($user))->toBeTrue()
        ->and($service->visibleSubmissionLimit($user, $list))->toBeNull()
        ->and($resolver->effectiveVisibleQuota($user, $list))->toBe((int) config('billing.unlimited_list_submission_cap'));
});

test('list unlimited override applies only to the targeted list', function () {
    $user = User::factory()->create();
    $upgradedList = DuaList::factory()->create(['user_id' => $user->id]);
    $freeList = DuaList::factory()->create(['user_id' => $user->id]);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $upgradedList->id,
        'entitlement_key' => EntitlementKey::ListUnlimitedOverride,
        'is_stackable' => false,
        'dedupe_key' => EntitlementGrant::dedupeKeyForListGrant($upgradedList->id, EntitlementKey::ListUnlimitedOverride),
    ]);

    $service = app(UserEntitlementService::class);

    expect($service->visibleSubmissionLimit($user, $upgradedList))->toBeNull()
        ->and($service->visibleSubmissionLimit($user, $freeList))->toBe((int) config('billing.free_visible_submissions_per_list'));
});

test('request pack grants increase visible quota on a single list', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $packSize = (int) config('billing.request_pack_size');

    EntitlementGrant::factory()->count(2)->create([
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => $packSize,
    ]);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveVisibleQuota($user, $list))
        ->toBe((int) config('billing.free_visible_submissions_per_list') + ($packSize * 2));
});

test('legacy premium entitlement bridges to unlimited forever behavior', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    UserEntitlement::query()->create([
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'source' => 'test',
        'reference' => 'legacy-premium',
        'unlocked_at' => now(),
    ]);

    $service = app(UserEntitlementService::class);

    expect($service->hasPremium($user))->toBeTrue()
        ->and($service->activeListLimit($user))->toBeNull()
        ->and($service->visibleSubmissionLimit($user, $list))->toBeNull();
});

test('persisted locked submissions are not viewable until unlocked', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $locked = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'locked_at_quota' => (int) config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
    ]);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->canViewSubmission($user, $locked))->toBeFalse();

    $locked->forceFill(['unlocked_at' => now()])->save();

    expect($resolver->canViewSubmission($user, $locked->fresh()))->toBeTrue();
});

test('entitlements api exposes grant based summary fields', function () {
    $user = $this->actingAsUser();

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 1,
    ]);

    $this->getJson('/api/v1/billing/entitlements')
        ->assertOk()
        ->assertJsonPath('data.extra_list_slots', 1)
        ->assertJsonPath('data.active_list_limit', (int) config('billing.default_list_capacity') + 1)
        ->assertJsonPath('data.unlimited_list_submission_cap', (int) config('billing.unlimited_list_submission_cap'));
});
