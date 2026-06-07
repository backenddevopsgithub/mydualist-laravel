<?php

use App\Models\DuaList;
use App\Models\User;

test('create list requires authentication', function () {
    $this->postJson('/api/v1/lists', [
        'title' => 'New List',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ])->assertUnauthorized();
});

test('authenticated user can create a dua list', function () {
    $user = $this->actingAsUser();

    $response = $this->postJson('/api/v1/lists', [
        'title' => 'API Hajj List',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'List created successfully.')
        ->assertJsonPath('data.title', 'API Hajj List')
        ->assertJsonPath('data.status', DuaList::STATUS_ACTIVE);

    $this->assertDatabaseHas('dua_lists', [
        'user_id' => $user->id,
        'title' => 'API Hajj List',
    ]);
});

test('create list enforces free tier list limit', function () {
    $user = $this->actingAsUser();
    DuaList::factory()->count(2)->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/lists', [
        'title' => 'Blocked List',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['billing']);
});

test('authenticated user can update owned list', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
    ]);

    $this->patchJson('/api/v1/lists/'.$list->id, [
        'title' => 'Updated Title',
        'occasion' => 'ramadan',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
    ])->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.occasion', 'ramadan');
});

test('user cannot update another users list', function () {
    $owner = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAsUser();

    $this->patchJson('/api/v1/lists/'.$list->id, [
        'title' => 'Hijacked',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ])->assertNotFound();
});

test('user can archive restore and delete owned lists', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->patchJson('/api/v1/lists/'.$list->id.'/archive')
        ->assertOk()
        ->assertJsonPath('data.status', DuaList::STATUS_ARCHIVED);

    $this->patchJson('/api/v1/lists/'.$list->id.'/restore')
        ->assertOk()
        ->assertJsonPath('data.status', DuaList::STATUS_ACTIVE);

    $this->deleteJson('/api/v1/lists/'.$list->id)
        ->assertOk()
        ->assertJsonPath('message', 'List deleted successfully.');

    $this->assertSoftDeleted('dua_lists', ['id' => $list->id]);
});

test('create list validates required fields', function () {
    $this->actingAsUser();

    $this->postJson('/api/v1/lists', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'occasion', 'start_date', 'end_date']);
});
