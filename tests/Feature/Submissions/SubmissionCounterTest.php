<?php

use App\Domains\Submissions\Actions\CreatePersonalDuaAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\SubmissionCounterService;
use Illuminate\Support\Facades\DB;

test('creating public submissions increments denormalized counters', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => [
            'Please make dua for my family.',
            'Please make dua for ease in my exams.',
        ],
    ])->assertRedirect();

    $duaList->refresh();

    expect($duaList->submissions_count)->toBe(2)
        ->and($duaList->pending_submissions_count)->toBe(2)
        ->and($duaList->non_personal_submissions_count)->toBe(2)
        ->and($duaList->completed_submissions_count)->toBe(0)
        ->and($duaList->submissions_count)->toBe(
            $duaList->pending_submissions_count
            + $duaList->completed_submissions_count
            + $duaList->hidden_submissions_count
            + $duaList->archived_submissions_count
            + $duaList->reported_submissions_count,
        );
});

test('creating a personal dua increments totals without non personal counter', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    app(CreatePersonalDuaAction::class)($duaList, $user, 'Personal dua content');

    $duaList->refresh();

    expect($duaList->submissions_count)->toBe(1)
        ->and($duaList->pending_submissions_count)->toBe(1)
        ->and($duaList->non_personal_submissions_count)->toBe(0);
});

test('completing and undoing a submission updates status counters', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $duaList->refresh();
    expect($duaList->pending_submissions_count)->toBe(1);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    $duaList->refresh();
    expect($duaList->pending_submissions_count)->toBe(0)
        ->and($duaList->completed_submissions_count)->toBe(1);

    app(TransitionDuaSubmissionStatusAction::class)($submission->fresh(), DuaSubmissionStatus::Pending);

    $duaList->refresh();
    expect($duaList->pending_submissions_count)->toBe(1)
        ->and($duaList->completed_submissions_count)->toBe(0);
});

test('hiding and unhiding a submission updates status counters', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Hidden);

    $duaList->refresh();
    expect($duaList->pending_submissions_count)->toBe(0)
        ->and($duaList->hidden_submissions_count)->toBe(1);

    app(TransitionDuaSubmissionStatusAction::class)($submission->fresh(), DuaSubmissionStatus::Pending);

    $duaList->refresh();
    expect($duaList->pending_submissions_count)->toBe(1)
        ->and($duaList->hidden_submissions_count)->toBe(0);
});

test('soft deleting a submission decrements counters', function () {
    $duaList = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed,
        'is_personal_dua' => false,
    ]);

    $duaList->refresh();
    expect($duaList->submissions_count)->toBe(1)
        ->and($duaList->completed_submissions_count)->toBe(1);

    $submission->delete();

    $duaList->refresh();
    expect($duaList->submissions_count)->toBe(0)
        ->and($duaList->completed_submissions_count)->toBe(0)
        ->and($duaList->non_personal_submissions_count)->toBe(0);
});

test('reconcile command repairs counters from dua_submissions', function () {
    $duaList = DuaList::factory()->create([
        'submissions_count' => 0,
        'pending_submissions_count' => 0,
        'completed_submissions_count' => 0,
        'hidden_submissions_count' => 0,
        'archived_submissions_count' => 0,
        'reported_submissions_count' => 0,
        'non_personal_submissions_count' => 0,
    ]);

    SubmissionCounterService::withoutCounterUpdates(function () use ($duaList): void {
        DuaSubmission::factory()->create([
            'dua_list_id' => $duaList->id,
            'status' => DuaSubmissionStatus::Pending,
            'is_personal_dua' => false,
        ]);
        DuaSubmission::factory()->create([
            'dua_list_id' => $duaList->id,
            'status' => DuaSubmissionStatus::Completed,
            'is_personal_dua' => true,
        ]);
    });

    $duaList->refresh();
    expect($duaList->submissions_count)->toBe(0);

    $this->artisan('submissions:reconcile-counters', ['--list' => (string) $duaList->id])
        ->assertSuccessful();

    $duaList->refresh();
    expect($duaList->submissions_count)->toBe(2)
        ->and($duaList->pending_submissions_count)->toBe(1)
        ->and($duaList->completed_submissions_count)->toBe(1)
        ->and($duaList->non_personal_submissions_count)->toBe(1);
});

test('status counts read from denormalized columns without extra count queries', function () {
    $duaList = DuaList::factory()->create();
    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Hidden,
    ]);

    $duaList->refresh();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $counts = app(\App\Domains\Submissions\Services\DuaSubmissionQueryService::class)->statusCounts($duaList);

    $submissionCountQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_submissions`')
            && str_contains(strtolower($query), 'count('));

    expect($counts[DuaSubmissionStatus::Pending->value])->toBe(1)
        ->and($counts[DuaSubmissionStatus::Hidden->value])->toBe(1)
        ->and($submissionCountQueries)->toBeEmpty();
});
