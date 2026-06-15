<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;

test('owner can create a personal dua via api', function () {
    $user = User::factory()->create([
        'first_name' => 'Arsalan',
        'last_name' => 'Hajj',
        'email' => 'owner@example.com',
    ]);
    $this->actingAsUser($user);
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/lists/'.$list->id.'/personal-duas', [
        'content' => 'Please make dua for my parents.',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Personal dua added.')
        ->assertJsonPath('data.content', 'Please make dua for my parents.')
        ->assertJsonPath('data.is_personal_dua', true)
        ->assertJsonPath('data.display_name', 'Arsalan Hajj')
        ->assertJsonPath('data.status', 'pending');

    expect(DuaSubmission::query()->count())->toBe(1)
        ->and(DuaSubmission::query()->first()->is_personal_dua)->toBeTrue();
});

test('personal dua api validates content', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/lists/'.$list->id.'/personal-duas', [
        'content' => '',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);

    expect(DuaSubmission::query()->count())->toBe(0);
});

test('personal dua api rejects another users list', function () {
    $owner = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAsUser();

    $this->postJson('/api/v1/lists/'.$list->id.'/personal-duas', [
        'content' => 'Please make dua for me.',
    ])->assertNotFound();

    expect(DuaSubmission::query()->count())->toBe(0);
});

test('personal duas appear in submissions index with is_personal_dua flag', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Network dua request',
    ]);

    $this->postJson('/api/v1/lists/'.$list->id.'/personal-duas', [
        'content' => 'My personal dua.',
    ])->assertCreated();

    $response = $this->getJson('/api/v1/lists/'.$list->id.'/submissions')->assertOk();

    $personal = collect($response->json('data'))->firstWhere('is_personal_dua', true);
    $regular = collect($response->json('data'))->firstWhere('is_personal_dua', false);

    expect($personal)->not->toBeNull()
        ->and($personal['content'])->toBe('My personal dua.')
        ->and($regular)->not->toBeNull()
        ->and($regular['content'])->toBe('Network dua request');
});

test('free owner can view and moderate personal duas beyond submission limit via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->count(30)->create(['dua_list_id' => $list->id]);

    $personal = DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $list->id,
        'user_id' => $user->id,
        'content' => 'Personal dua beyond the free limit.',
    ]);

    $response = $this->getJson('/api/v1/lists/'.$list->id.'/submissions?per_page=50')->assertOk();

    $payload = collect($response->json('data'))->firstWhere('id', $personal->id);

    expect($payload['locked'])->toBeFalse()
        ->and($payload['is_personal_dua'])->toBeTrue()
        ->and($payload['content'])->toBe('Personal dua beyond the free limit.');

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$personal->id.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.is_personal_dua', true);

    expect($personal->refresh()->status)->toBe(DuaSubmissionStatus::Completed);
});

test('free owner still cannot moderate locked non personal submissions via api', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $list->id]);
    $locked = DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/submissions/'.$locked->id.'/complete')
        ->assertForbidden();
});

test('premium owner can moderate locked non personal submissions via api', function () {
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
