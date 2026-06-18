<?php

use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('dashboard summary aggregates submission totals from dua list counters', function () {
    $user = User::factory()->create();
    $activeList = DuaList::factory()->create(['user_id' => $user->id, 'status' => DuaList::STATUS_ACTIVE]);
    $archivedList = DuaList::factory()->create(['user_id' => $user->id, 'status' => DuaList::STATUS_ARCHIVED]);

    DuaSubmission::factory()->count(2)->create(['dua_list_id' => $activeList->id]);
    $completed = DuaSubmission::factory()->create(['dua_list_id' => $archivedList->id]);
    app(TransitionDuaSubmissionStatusAction::class)($completed, DuaSubmissionStatus::Completed);

    $summary = app(DuaListQueryService::class)->dashboardSummary($user);

    expect($summary['active_lists_count'])->toBe(1)
        ->and($summary['archived_lists_count'])->toBe(1)
        ->and($summary['total_submissions_count'])->toBe(3)
        ->and($summary['completed_duas_count'])->toBe(1);
});

test('dashboard summary does not query dua_submissions', function () {
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(DuaListQueryService::class)->dashboardSummary($user);

    $submissionQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_submissions`'));

    expect($submissionQueries)->toBeEmpty();
});

test('profile endpoint stats match dashboard summary without querying dua_submissions', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->count(2)->create(['dua_list_id' => $list->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->getJson('/api/v1/profile')->assertOk();

    $submissionQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_submissions`'));

    expect($submissionQueries)->toBeEmpty()
        ->and($response->json('data.stats.total_submissions_count'))->toBe(2)
        ->and($response->json('data.stats.active_lists_count'))->toBe(1);
});

test('lists index dashboard summary reflects denormalized counters', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->count(3)->create(['dua_list_id' => $list->id]);

    $this->getJson('/api/v1/lists')
        ->assertOk()
        ->assertJsonPath('meta.dashboard_summary.total_submissions_count', 3)
        ->assertJsonPath('meta.dashboard_summary.active_lists_count', 1);
});

test('dashboard summary returns zeros for users without lists', function () {
    $user = User::factory()->create();

    expect(app(DuaListQueryService::class)->dashboardSummary($user))->toBe([
        'active_lists_count' => 0,
        'archived_lists_count' => 0,
        'total_submissions_count' => 0,
        'completed_duas_count' => 0,
    ]);
});
