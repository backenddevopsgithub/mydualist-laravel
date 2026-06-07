<?php

use App\Models\DuaList;
use App\Models\User;

test('public list endpoint does not require authentication', function () {
    $owner = User::factory()->create(['name' => 'List Owner']);
    $list = DuaList::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'public-hajj-list',
        'title' => 'Public Hajj List',
    ]);

    $this->getJson('/api/v1/public/lists/public-hajj-list')
        ->assertOk()
        ->assertJsonPath('message', 'Public list retrieved.')
        ->assertJsonPath('data.slug', 'public-hajj-list')
        ->assertJsonPath('data.title', 'Public Hajj List')
        ->assertJsonPath('data.owner.name', 'List Owner')
        ->assertJsonStructure([
            'data' => [
                'accepts_submissions',
                'submissions_count',
                'completed_submissions_count',
            ],
        ])
        ->assertJsonMissing(['data' => ['owner' => ['email' => $owner->email]]]);
});

test('public list endpoint returns not found for unknown slug', function () {
    $this->getJson('/api/v1/public/lists/does-not-exist')->assertNotFound();
});

test('public list endpoint reflects closed state for archived lists', function () {
    $list = DuaList::factory()->create([
        'slug' => 'archived-public-list',
        'status' => DuaList::STATUS_ARCHIVED,
    ]);

    $this->getJson('/api/v1/public/lists/archived-public-list')
        ->assertOk()
        ->assertJsonPath('data.accepts_submissions', false)
        ->assertJsonPath('data.closed_reason', fn (?string $reason) => $reason !== null);
});
