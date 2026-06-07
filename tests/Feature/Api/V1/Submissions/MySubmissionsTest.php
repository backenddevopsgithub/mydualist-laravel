<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

test('my submissions requires authentication', function () {
    $this->getJson('/api/v1/my-submissions')->assertUnauthorized();
});

test('authenticated user can list their own submitted duas', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id, 'title' => 'My List']);
    DuaSubmission::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $list->id,
        'content' => 'My personal dua request',
    ]);

    $this->getJson('/api/v1/my-submissions')
        ->assertOk()
        ->assertJsonPath('message', 'Submissions retrieved.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.content', 'My personal dua request')
        ->assertJsonPath('data.0.dua_list.title', 'My List')
        ->assertJsonStructure([
            'data' => [[
                'id',
                'content',
                'dua_list' => ['id', 'title', 'slug'],
            ]],
            'meta',
            'links',
        ]);
});

test('my submissions only returns current users records', function () {
    $user = $this->actingAsUser();
    $other = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $other->id]);

    DuaSubmission::factory()->create([
        'user_id' => $other->id,
        'dua_list_id' => $list->id,
    ]);

    $this->getJson('/api/v1/my-submissions')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
