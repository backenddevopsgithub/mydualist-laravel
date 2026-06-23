<?php

use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Jobs\ProcessDuaSubmissionsCreatedSideEffects;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('public submission page always renders visible submit button label', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'slug' => 'qa-submit-label-list',
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Submit Dua Requests', false);
});

test('batch submission uses a single insert and defers side effects until after commit', function () {
    Notification::fake();
    Bus::fake([ProcessDuaSubmissionsCreatedSideEffects::class]);

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
        'email_frequency' => 'every_submission',
    ]);

    $duas = collect(range(1, 10))
        ->map(fn (int $number): string => "Please make dua for request {$number}.")
        ->all();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $startedAt = hrtime(true);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Batch',
        'last_name' => 'Tester',
        'email' => 'batch-tester@example.com',
        'gender' => 'female',
        'submission_batch_key' => (string) Str::uuid(),
        'duas' => $duas,
    ]);

    $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
    $insertQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => preg_match('/insert\s+into\s+[`"]?dua_submissions[`"]?/i', $query) === 1);

    expect(DuaSubmission::query()->count())->toBe(10)
        ->and($insertQueries)->toHaveCount(1)
        ->and($elapsedMs)->toBeLessThan(2000);

    Bus::assertDispatched(ProcessDuaSubmissionsCreatedSideEffects::class);
    Notification::assertNothingSent();
});

test('batch submission scales insert count for twenty five duas', function () {
    Bus::fake([ProcessDuaSubmissionsCreatedSideEffects::class]);

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $duas = collect(range(1, 25))
        ->map(fn (int $number): string => "Please make dua for request {$number}.")
        ->all();

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Large',
        'last_name' => 'Batch',
        'email' => 'large-batch@example.com',
        'gender' => 'female',
        'submission_batch_key' => (string) Str::uuid(),
        'duas' => $duas,
    ]);

    $insertQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => preg_match('/insert\s+into\s+[`"]?dua_submissions[`"]?/i', $query) === 1);

    expect(DuaSubmission::query()->count())->toBe(25)
        ->and($insertQueries)->toHaveCount(1);
});
