<?php

use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('user can login with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'Password123!',
        'device_name' => 'web',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'login@example.com')
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

    expect($response->json('data.token'))->not->toBeEmpty();
});

test('login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Invalid credentials.',
            'error_code' => 'invalid_credentials',
        ]);
});

test('login rejects unknown email with generic unauthorized response', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'missing@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('error_code', 'invalid_credentials');
});

test('login rejects inactive accounts', function () {
    User::factory()->suspended()->create([
        'email' => 'inactive@example.com',
        'password' => 'Password123!',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'inactive@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertForbidden()
        ->assertJsonPath('error_code', 'account_inactive');
});

test('login validates required fields', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('suspended status enum prevents login', function () {
    $user = User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => 'Password123!',
        'status' => UserStatus::Suspended,
    ]);

    expect($user->isActive())->toBeFalse();
});
