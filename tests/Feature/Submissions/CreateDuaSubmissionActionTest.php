<?php

use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('create submission action reads non personal quota from list counter', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
        'non_personal_submissions_count' => 7,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Amina',
        'email' => 'amina@example.com',
        'gender' => 'female',
        'content' => 'Please make dua for my family.',
    ]);

    $nonPersonalCountQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'is_personal_dua'));

    expect($nonPersonalCountQueries)->toBeEmpty()
        ->and($duaList->fresh()->non_personal_submissions_count)->toBe(8);
});

test('create submission action batches counter updates for multi dua requests', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Batch',
        'email' => 'batch@example.com',
        'gender' => 'female',
        'duas' => [
            'First dua request here.',
            'Second dua request here.',
        ],
    ]);

    $duaList->refresh();

    expect($duaList->submissions_count)->toBe(2)
        ->and($duaList->pending_submissions_count)->toBe(2)
        ->and($duaList->non_personal_submissions_count)->toBe(2);
});

test('create submission action enforces per email limit under list lock', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    DuaSubmission::factory()->count(34)->create([
        'dua_list_id' => $duaList->id,
        'email' => 'same@example.com',
    ]);

    expect(fn () => app(CreateDuaSubmissionAction::class)($duaList, [
        'email' => 'same@example.com',
        'gender' => 'male',
        'duas' => [
            'First extra dua request.',
            'Second extra dua request.',
        ],
    ]))->toThrow(ValidationException::class);

    expect(DuaSubmission::query()->where('dua_list_id', $duaList->id)->count())->toBe(34);
});

test('create submission action rejects closed lists before counting', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ARCHIVED,
        'published_at' => now(),
    ]);

    expect(fn () => app(CreateDuaSubmissionAction::class)($duaList, [
        'email' => 'guest@example.com',
        'gender' => 'male',
        'content' => 'This should fail.',
    ]))->toThrow(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception): bool {
        return $exception->getStatusCode() === 403;
    });

    expect(DuaSubmission::query()->where('dua_list_id', $duaList->id)->exists())->toBeFalse();
});
