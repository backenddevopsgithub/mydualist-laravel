<?php

use App\Models\DuaList;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('lists index requires authentication', function () {
    $this->getJson('/api/v1/lists')->assertUnauthorized();
});

test('lists index requires verified email', function () {
    $user = User::factory()->unverified()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/lists')->assertForbidden();
});

test('authenticated user can list active dua lists', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id, 'title' => 'My Hajj List']);
    DuaList::factory()->create(['user_id' => $user->id, 'status' => DuaList::STATUS_ARCHIVED]);

    $response = $this->getJson('/api/v1/lists')->assertOk();

    $response->assertJsonPath('message', 'Lists retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $list->id)
        ->assertJsonPath('data.0.title', 'My Hajj List')
        ->assertJsonStructure([
            'data' => [[
                'id', 'title', 'slug', 'occasion', 'status', 'submissions_count', 'accepts_submissions',
            ]],
            'meta' => [
                'current_page', 'last_page', 'per_page', 'total',
                'dashboard_summary' => [
                    'active_lists_count',
                    'archived_lists_count',
                    'total_submissions_count',
                    'completed_duas_count',
                ],
            ],
            'links',
        ]);
});

test('lists index can filter archived lists', function () {
    $user = $this->actingAsUser();
    DuaList::factory()->create(['user_id' => $user->id]);
    $archived = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Archived List',
        'status' => DuaList::STATUS_ARCHIVED,
    ]);

    $this->getJson('/api/v1/lists?status=archived')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $archived->id);
});

test('user cannot list another users dua lists via show endpoint', function () {
    $owner = User::factory()->create();
    $other = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->getJson('/api/v1/lists/'.$list->id)->assertNotFound();
});

test('authenticated user can show owned list detail', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Detail List',
        'display_order' => 'date',
    ]);

    $this->getJson('/api/v1/lists/'.$list->id)
        ->assertOk()
        ->assertJsonPath('data.id', $list->id)
        ->assertJsonPath('data.title', 'Detail List')
        ->assertJsonPath('data.display_order', 'date')
        ->assertJsonStructure(['data' => ['entitlements' => ['has_premium', 'locked_submission_count']]]);
});

test('lists index validates query parameters', function () {
    $this->actingAsUser();

    $this->getJson('/api/v1/lists?status=invalid')->assertUnprocessable();
});
