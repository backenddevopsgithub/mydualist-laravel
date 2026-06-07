<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;

test('owner can complete and undo a submission via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/undo')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');
});

test('owner can hide unhide and archive submissions via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/hide')
        ->assertOk()
        ->assertJsonPath('data.status', 'hidden');

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/unhide')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/archive')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

test('owner can report a submission via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    $this->postJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/report', [
        'report_reason' => 'spam',
    ])->assertOk()
        ->assertJsonPath('data.status', 'reported')
        ->assertJsonPath('data.report_reason', 'spam');

    expect($submission->fresh()->reported_at)->not->toBeNull();
});

test('submission moderation rejects another users list', function () {
    $owner = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->actingAsUser();

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/complete')
        ->assertNotFound();
});

test('free owner cannot moderate locked submissions via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $list->id]);
    $locked = DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$locked->id.'/complete')
        ->assertForbidden();
});

test('premium owner can moderate locked submissions via api', function () {
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

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $list->id]);
    $locked = DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$locked->id.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

test('report submission validates reason', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->postJson('/api/v1/lists/'.$list->id.'/submissions/'.$submission->id.'/report', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['report_reason']);
});
