<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;

test('submissions index requires authentication', function () {
    $list = DuaList::factory()->create();

    $this->getJson('/api/v1/lists/'.$list->id.'/submissions')->assertUnauthorized();
});

test('user cannot view submissions for another users list', function () {
    $owner = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAsUser();

    $this->getJson('/api/v1/lists/'.$list->id.'/submissions')->assertNotFound();
});

test('owner can list submissions with pagination and filters', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Please make dua for my family',
        'status' => DuaSubmissionStatus::Pending,
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Completed dua request',
        'status' => DuaSubmissionStatus::Completed,
    ]);

    $this->getJson('/api/v1/lists/'.$list->id.'/submissions?status=completed')
        ->assertOk()
        ->assertJsonPath('message', 'Submissions retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'completed')
        ->assertJsonStructure([
            'meta' => [
                'status_counts' => ['pending', 'completed', 'hidden', 'archived', 'reported'],
                'has_premium',
                'visible_submission_limit',
                'locked_submission_count',
            ],
        ]);
});

test('free owner sees locked submissions in api payload', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $list->id]);
    $locked = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Locked dua content',
    ]);

    $response = $this->getJson('/api/v1/lists/'.$list->id.'/submissions')->assertOk();

    $lockedPayload = collect($response->json('data'))->firstWhere('id', $locked->id);

    expect($lockedPayload)->not->toBeNull()
        ->and($lockedPayload['locked'])->toBeTrue()
        ->and($lockedPayload)->not->toHaveKey('content');
});

test('premium owner sees full submission content in api payload', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    UserEntitlement::query()->create([
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'source' => 'test',
        'reference' => 'test-premium',
        'unlocked_at' => now(),
    ]);

    DuaSubmission::factory()->count(26)->create(['dua_list_id' => $list->id]);
    $submission = $list->submissions()->latest('id')->first();

    $response = $this->getJson('/api/v1/lists/'.$list->id.'/submissions')->assertOk();

    $payload = collect($response->json('data'))->firstWhere('id', $submission->id);

    expect($payload['locked'])->toBeFalse()
        ->and($payload['content'])->toBe($submission->content);
});

test('submissions index supports search filter', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'first_name' => 'Unique',
        'last_name' => 'Searcher',
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'first_name' => 'Other',
        'last_name' => 'Person',
    ]);

    $this->getJson('/api/v1/lists/'.$list->id.'/submissions?search=Unique')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'Unique Searcher');
});
