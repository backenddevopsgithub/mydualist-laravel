<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('logout revokes current access token', function () {
    User::factory()->create([
        'email' => 'logout@example.com',
        'password' => 'Password123!',
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'logout@example.com',
        'password' => 'Password123!',
    ]);

    $token = $login->json('data.token');

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout successful.');

    expect(PersonalAccessToken::query()->count())->toBe(0)
        ->and(PersonalAccessToken::findToken($token))->toBeNull();

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

test('logout requires authentication', function () {
    $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
});
