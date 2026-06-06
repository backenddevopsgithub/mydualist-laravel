<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('login issues sanctum token', function () {
    User::factory()->create([
        'email' => 'token@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'token@example.com',
        'password' => 'Password123!',
    ]);

    $token = $response->json('data.token');

    expect(PersonalAccessToken::query()->where('name', 'api-token')->exists())->toBeTrue()
        ->and($token)->toBeString()->not->toBeEmpty();
});

test('registration issues sanctum token', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Token User',
        'email' => 'register-token@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated();

    expect(PersonalAccessToken::query()->count())->toBe(1);
});

test('authenticated token can access me endpoint', function () {
    ['token' => $token, 'user' => $user] = $this->registerUser([
        'email' => 'me-token@example.com',
    ]);

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

test('invalid token cannot access protected routes', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
