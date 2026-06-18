<?php

use App\Domains\Billing\Services\EntitlementGrantService;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Enums\EntitlementKey;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('entitlement grant lookups are memoized within a request', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 5,
    ]);

    $grants = app(EntitlementGrantService::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $grants->quantity($user, EntitlementKey::ListVisibleSubmissionPack, $list->id);
    $grants->quantity($user, EntitlementKey::ListVisibleSubmissionPack, $list->id);

    $grantQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'entitlement_grants'));

    expect($grantQueries)->toHaveCount(1);
});

test('repeated entitlement resolver calls reuse memoized results', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'is_stackable' => true,
        'dedupe_key' => null,
        'quantity' => 3,
    ]);

    $entitlements = app(UserEntitlementService::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $entitlements->visibleSubmissionLimit($user, $list);
    $grantQueriesAfterFirst = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'entitlement_grants'))
        ->count();

    $entitlements->visibleSubmissionLimit($user, $list);
    $entitlements->hasPremium($user);
    $entitlements->hasPremium($user);
    $entitlements->lockedSubmissionCount($user, $list);

    $grantQueriesAfterRepeated = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'entitlement_grants'))
        ->count();

    expect($grantQueriesAfterRepeated)->toBe($grantQueriesAfterFirst);
});

test('list submission page avoids duplicate owned list fetch', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('dashboard.lists.show', $list))
        ->assertOk();

    $userScopedListQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'dua_lists')
            && str_contains(strtolower($query['query']), 'user_id'));

    expect($userScopedListQueries)->toBeEmpty();
});
