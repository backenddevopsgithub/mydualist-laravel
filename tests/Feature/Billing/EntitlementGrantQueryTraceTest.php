<?php

use App\Domains\Billing\Services\EntitlementGrantService;
use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\ListSubmissionQuotaService;
use App\Enums\EntitlementKey;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('production list 1889 scenario resolves quota 75 with exact grant attributes', function () {
    config(['billing.free_visible_submissions_per_list' => 50]);

    $owner = User::factory()->create(['id' => 1677]);
    $list = DuaList::factory()->create(['id' => 1889, 'user_id' => 1677, 'wp_post_id' => 15912]);

    EntitlementGrant::factory()->create([
        'user_id' => 1677,
        'dua_list_id' => 1889,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'source_purchase_id' => null,
        'expires_at' => null,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    DB::enableQueryLog();

    $inspect = app(ListSubmissionQuotaService::class)->inspect(
        User::find(1677),
        DuaList::find(1889),
    );

    $queries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'entitlement_grants'))
        ->values()
        ->all();

    expect($inspect)->toBe([
        'visible' => 0,
        'quota' => 75,
        'exceeds' => false,
    ])
        ->and(app(EntitlementGrantService::class)->listScopedQuantity(
            $owner,
            EntitlementKey::ListVisibleSubmissionPack,
            1889,
        ))->toBe(25)
        ->and($queries)->not->toBeEmpty();
});

test('list scoped quantity matches grants inserted outside eloquent casts', function () {
    config(['billing.free_visible_submissions_per_list' => 50]);

    $owner = User::factory()->create(['id' => 1677]);
    DuaList::factory()->create(['id' => 1889, 'user_id' => 1677]);

    DB::table('entitlement_grants')->insert([
        'user_id' => 1677,
        'dua_list_id' => 1889,
        'entitlement_key' => 'list.visible_submission_pack',
        'quantity' => 25,
        'is_stackable' => true,
        'dedupe_key' => 'purchase:169',
        'source_purchase_id' => null,
        'granted_at' => now(),
        'expires_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bonus = app(EntitlementGrantService::class)->listScopedQuantity(
        User::find(1677),
        EntitlementKey::ListVisibleSubmissionPack,
        1889,
    );

    expect($bonus)->toBe(25)
        ->and(app(EntitlementResolverService::class)->effectiveVisibleQuota(User::find(1677), DuaList::find(1889)))
        ->toBe(75);
});

test('list scoped grants count by dua_list_id even when purchaser differs from list owner', function () {
    config(['billing.free_visible_submissions_per_list' => 50]);

    $listOwner = User::factory()->create(['id' => 2000]);
    User::factory()->create(['id' => 1677]);
    $list = DuaList::factory()->create(['id' => 1889, 'user_id' => 2000, 'wp_post_id' => 15912]);

    EntitlementGrant::factory()->create([
        'user_id' => 1677,
        'dua_list_id' => 1889,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'expires_at' => null,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    $legacyLookup = (int) EntitlementGrant::query()
        ->where('user_id', $listOwner->id)
        ->where('dua_list_id', 1889)
        ->where('entitlement_key', EntitlementKey::ListVisibleSubmissionPack->value)
        ->sum('quantity');

    $inspect = app(ListSubmissionQuotaService::class)->inspect($listOwner, $list);

    expect($legacyLookup)->toBe(0)
        ->and($inspect['quota'])->toBe(75);
});
