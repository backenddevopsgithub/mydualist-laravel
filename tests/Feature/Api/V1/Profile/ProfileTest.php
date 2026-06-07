<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

test('profile endpoint requires authentication', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
});

test('authenticated user can fetch profile with stats and entitlements', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->create(['dua_list_id' => $list->id]);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('message', 'Profile retrieved.')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.entitlements.plan', 'Free')
        ->assertJsonPath('data.stats.active_lists_count', 1)
        ->assertJsonPath('data.stats.total_submissions_count', 1)
        ->assertJsonStructure([
            'data' => [
                'first_name',
                'last_name',
                'entitlements' => ['plan', 'has_premium', 'can_create_list'],
                'stats' => ['active_lists_count', 'archived_lists_count', 'total_submissions_count', 'completed_duas_count'],
            ],
        ]);
});

test('profile endpoint does not expose sensitive fields', function () {
    $user = User::factory()->withWordPressPassword()->create();

    $this->actingAsUser($user);

    $response = $this->getJson('/api/v1/profile')->assertOk();

    expect($response->json('data'))->not->toHaveKeys(['password', 'wp_password_hash', 'remember_token']);
});
