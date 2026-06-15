<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;

test('public submission api does not require authentication', function () {
    $list = DuaList::factory()->create(['slug' => 'public-submit-list']);

    $this->postJson('/api/v1/public/lists/public-submit-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'gender' => 'male',
        'terms' => '1',
        'content' => 'Please make dua for my family.',
    ])->assertCreated()
        ->assertJsonPath('data.count', 1)
        ->assertJsonPath('data.submissions.0.status', 'pending');

    $this->assertDatabaseHas('dua_submissions', [
        'dua_list_id' => $list->id,
        'email' => 'guest@example.com',
        'gender' => 'male',
    ]);
});

test('public submission api requires gender', function () {
    DuaList::factory()->create(['slug' => 'gender-required-list']);

    $this->postJson('/api/v1/public/lists/gender-required-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'terms' => '1',
        'content' => 'Please make dua for my family.',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['gender']);
});

test('public submission api rejects closed lists', function () {
    DuaList::factory()->create([
        'slug' => 'closed-list',
        'status' => DuaList::STATUS_ARCHIVED,
    ]);

    $this->postJson('/api/v1/public/lists/closed-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'gender' => 'male',
        'terms' => '1',
        'content' => 'This should fail.',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

test('public submission api validates content', function () {
    DuaList::factory()->create(['slug' => 'validate-list']);

    $this->postJson('/api/v1/public/lists/validate-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'gender' => 'female',
        'terms' => '1',
        'content' => 'x',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

test('public submission api supports multiple duas in one request', function () {
    $list = DuaList::factory()->create(['slug' => 'batch-list']);

    $this->postJson('/api/v1/public/lists/batch-list/submissions', [
        'first_name' => 'Batch',
        'last_name' => 'User',
        'email' => 'batch@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => [
            'First dua request here.',
            'Second dua request here.',
        ],
    ])->assertCreated()
        ->assertJsonPath('data.count', 2);

    expect(DuaSubmission::query()->where('dua_list_id', $list->id)->count())->toBe(2);
});

test('public submission api returns not found for unknown slug', function () {
    $this->postJson('/api/v1/public/lists/missing-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'gender' => 'male',
        'terms' => '1',
        'content' => 'Please make dua.',
    ])->assertNotFound();
});
