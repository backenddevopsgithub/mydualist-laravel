<?php

use App\Domains\Billing\Services\ListSubmissionQuotaService;
use App\Enums\EntitlementKey;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService;
use App\Services\LegacyImport\Validation\MigrationValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOwnedList(): array
{
    $owner = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);

    return [$owner, $list];
}

test('list submission quota service counts only unlocked regular submissions', function () {
    [$owner, $list] = createOwnedList();

    DuaSubmission::factory()->count(3)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    DuaSubmission::factory()->count(2)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'is_personal_dua' => false,
        'unlocked_at' => now(),
    ]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => true,
    ]);

    $quota = app(ListSubmissionQuotaService::class);

    expect($quota->unlockedRegularSubmissionCount($list))->toBe(3);
});

test('list submission quota service includes request pack grants attached to the list', function () {
    [$owner, $list] = createOwnedList();
    $purchaser = User::factory()->create();

    EntitlementGrant::factory()->create([
        'user_id' => $purchaser->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    $quota = app(ListSubmissionQuotaService::class);

    expect($quota->effectiveVisibleQuota($owner, $list))
        ->toBe((int) config('billing.free_visible_submissions_per_list') + 25);
});

test('migration validation does not flag production style mixed lock state within quota', function () {
    config(['billing.free_visible_submissions_per_list' => 50]);

    [$owner, $list] = createOwnedList();
    $list->forceFill(['wp_post_id' => 15912])->save();

    EntitlementGrant::factory()->create([
        'user_id' => User::factory()->create()->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    DuaSubmission::factory()->count(57)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    DuaSubmission::factory()->count(5)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'is_personal_dua' => false,
        'unlocked_at' => now(),
    ]);

    $report = app(MigrationValidationService::class)->validate();

    expect(collect($report->validation['mismatches'] ?? [])
        ->firstWhere('wp_post_id', 15912))->toBeNull();
});

test('migration validation flags visible submissions above base quota only', function () {
    [$owner, $list] = createOwnedList();

    DuaSubmission::factory()->count((int) config('billing.free_visible_submissions_per_list') + 1)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    $report = app(MigrationValidationService::class)->validate();

    expect(collect($report->validation['mismatches'] ?? [])
        ->contains(fn (array $mismatch): bool => $mismatch['dua_list_id'] === $list->id
            && $mismatch['type'] === 'visible_exceeds_quota'))->toBeTrue();
});

test('migration validation allows additional request pack quota', function () {
    [$owner, $list] = createOwnedList();
    $baseQuota = (int) config('billing.free_visible_submissions_per_list');

    EntitlementGrant::factory()->create([
        'user_id' => $owner->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    DuaSubmission::factory()->count($baseQuota + 25)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    $report = app(MigrationValidationService::class)->validate();

    expect(collect($report->validation['mismatches'] ?? [])
        ->contains(fn (array $mismatch): bool => $mismatch['dua_list_id'] === $list->id))->toBeFalse();
});

test('migration validation ignores unlimited list overrides', function () {
    [$owner, $list] = createOwnedList();

    EntitlementGrant::factory()->create([
        'user_id' => $owner->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListUnlimitedOverride,
        'quantity' => 1,
        'is_stackable' => false,
        'dedupe_key' => EntitlementGrant::dedupeKeyForListGrant($list->id, EntitlementKey::ListUnlimitedOverride),
    ]);

    DuaSubmission::factory()->count(200)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    $report = app(MigrationValidationService::class)->validate();

    expect(collect($report->validation['mismatches'] ?? [])
        ->contains(fn (array $mismatch): bool => $mismatch['dua_list_id'] === $list->id))->toBeFalse();
});

test('lock reconciliation uses the same visible quota inspection as migration validation', function () {
    config(['billing.free_visible_submissions_per_list' => 50]);

    [$owner, $list] = createOwnedList();

    EntitlementGrant::factory()->create([
        'user_id' => $owner->id,
        'dua_list_id' => $list->id,
        'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
        'quantity' => 25,
        'is_stackable' => true,
        'dedupe_key' => null,
    ]);

    DuaSubmission::factory()->count(57)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    DuaSubmission::factory()->count(5)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'is_personal_dua' => false,
        'unlocked_at' => now(),
    ]);

    $reconciliation = app(SubmissionLockReconciliationService::class)->reconcile(dryRun: true);

    expect(collect($reconciliation['mismatches'])
        ->contains(fn (array $mismatch): bool => ($mismatch['dua_list_id'] ?? null) === $list->id
            && ($mismatch['type'] ?? null) === 'visible_exceeds_quota'))->toBeFalse();
});

test('migration validation excludes locked submissions from visible count', function () {
    [$owner, $list] = createOwnedList();
    $baseQuota = (int) config('billing.free_visible_submissions_per_list');

    DuaSubmission::factory()->count($baseQuota)->create([
        'dua_list_id' => $list->id,
        'is_locked' => false,
        'is_personal_dua' => false,
    ]);

    DuaSubmission::factory()->count(10)->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'is_personal_dua' => false,
        'unlocked_at' => now(),
    ]);

    $report = app(MigrationValidationService::class)->validate();

    expect(collect($report->validation['mismatches'] ?? [])
        ->contains(fn (array $mismatch): bool => $mismatch['dua_list_id'] === $list->id))->toBeFalse();
});
