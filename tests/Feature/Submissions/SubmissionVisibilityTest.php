<?php

use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Enums\DuaSubmissionStatus;
use App\Enums\SubmissionLockReason;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService;
use Illuminate\Support\Facades\DB;

test('public submissions beyond visible quota are locked at insert time', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
        'non_personal_submissions_count' => (int) config('billing.free_visible_submissions_per_list'),
    ]);

    $submissions = app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Guest',
        'email' => 'guest@example.com',
        'gender' => 'male',
        'content' => 'Please remember me in your duas.',
    ]);

    expect($submissions)->toHaveCount(1)
        ->and($submissions->first()->is_locked)->toBeTrue()
        ->and($submissions->first()->locked_reason)->toBe(SubmissionLockReason::VisibleQuotaExhausted)
        ->and($submissions->first()->locked_at_quota)->toBe((int) config('billing.free_visible_submissions_per_list'));
});

test('can view submission uses persisted lock flags without rank count queries', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $locked = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'is_locked' => true,
        'locked_at_quota' => (int) config('billing.free_visible_submissions_per_list'),
        'locked_reason' => SubmissionLockReason::VisibleQuotaExhausted,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $canView = app(EntitlementResolverService::class)->canViewSubmission($user, $locked);

    $rankCountQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'count('));

    expect($canView)->toBeFalse()
        ->and($rankCountQueries)->toBeEmpty();
});

test('locked submission count reads persisted quota locked rows', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->count(30)->create(['dua_list_id' => $list->id]);

    app(SubmissionLockReconciliationService::class)->reconcile(false);

    expect(app(EntitlementResolverService::class)->lockedSubmissionCount($user, $list))
        ->toBe(30 - (int) config('billing.free_visible_submissions_per_list'));
});

test('list submission ordering uses lock columns instead of visible id plucks', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(26)->create(['dua_list_id' => $duaList->id]);
    app(SubmissionLockReconciliationService::class)->reconcile(false);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    $pluckQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => preg_match('/select\s+["`]?id["`]?\s+from\s+["`]?dua_submissions["`]?/i', $query) === 1);

    expect($orderedIds[0])->toBe(DuaSubmission::query()->where('dua_list_id', $duaList->id)->orderByDesc('id')->value('id'))
        ->and($pluckQueries)->toBeEmpty();
});

test('batch public submission assigns lock flags per rank in one request', function () {
    $owner = User::factory()->create();
    $quota = (int) config('billing.free_visible_submissions_per_list');
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
        'non_personal_submissions_count' => $quota - 1,
    ]);

    $submissions = app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Batch',
        'email' => 'batch@example.com',
        'gender' => 'female',
        'duas' => [
            'First dua at the quota boundary.',
            'Second dua beyond the quota.',
        ],
    ]);

    expect($submissions[0]->is_locked)->toBeFalse()
        ->and($submissions[1]->is_locked)->toBeTrue();
});
