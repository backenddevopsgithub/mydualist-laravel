<?php

use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Support\DuaListDisplayOrders;

test('display order by date sorts submissions by id ascending', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'display_order' => DuaListDisplayOrders::DATE,
    ]);

    $first = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Zara',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $second = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Adam',
        'gender' => 'male',
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$first->id, $second->id]);
});

test('display order by gender places male submissions before female submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'display_order' => DuaListDisplayOrders::GENDER,
    ]);

    $femaleFirst = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Aisha',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $male = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Bilal',
        'gender' => 'male',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $femaleSecond = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Fatima',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$male->id, $femaleFirst->id, $femaleSecond->id]);
});

test('display order by gender places legacy null gender submissions after male and female submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'display_order' => DuaListDisplayOrders::GENDER,
    ]);

    $legacy = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Legacy',
        'gender' => null,
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $female = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Aisha',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $male = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Bilal',
        'gender' => 'male',
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$male->id, $female->id, $legacy->id]);
});

test('display order by person sorts submissions alphabetically by first name', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'display_order' => DuaListDisplayOrders::PERSON,
    ]);

    $zara = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Zara',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $adam = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Adam',
        'gender' => 'male',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    $mariam = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Mariam',
        'gender' => 'female',
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$adam->id, $mariam->id, $zara->id]);
});

test('display order places locked submissions before visible submissions on free plans', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'display_order' => DuaListDisplayOrders::DATE,
    ]);

    $submissions = collect();

    for ($i = 1; $i <= 26; $i++) {
        $submissions->push(DuaSubmission::factory()->create([
            'dua_list_id' => $duaList->id,
            'first_name' => "Person {$i}",
            'gender' => $i % 2 === 0 ? 'male' : 'female',
            'status' => DuaSubmissionStatus::Pending,
        ]));
    }

    app(\App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService::class)->reconcile(false);

    $locked = $submissions->last();
    $visible = $submissions->first();

    $orderedIds = app(DuaSubmissionQueryService::class)
        ->paginateForList($duaList, ['status' => DuaSubmissionStatus::Pending->value], 50, $owner)
        ->pluck('id')
        ->all();

    expect($orderedIds[0])->toBe($locked->id)
        ->and($orderedIds)->toContain($visible->id)
        ->and(array_search($locked->id, $orderedIds, true))
        ->toBeLessThan(array_search($visible->id, $orderedIds, true));
});

test('public submission stores submitter gender for display ordering', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'Female',
        'content' => 'Please make dua for my family.',
    ]);

    expect(DuaSubmission::query()->first()->gender)->toBe('female');
});
