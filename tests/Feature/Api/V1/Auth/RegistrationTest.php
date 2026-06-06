<?php

use App\Domains\Auth\Notifications\VerifyEmailNotification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('user can register successfully', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Arsalan',
        'email' => 'arsalan@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'device_name' => 'iphone',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'status', 'avatar', 'email_verified_at'],
                'token',
                'token_type',
            ],
        ])
        ->assertJsonPath('data.user.role', 'user')
        ->assertJsonPath('data.user.status', 'active')
        ->assertJsonPath('data.token_type', 'Bearer');

    $this->assertDatabaseHas('users', [
        'email' => 'arsalan@example.com',
        'role' => 'user',
        'status' => 'active',
    ]);

    $user = User::query()->where('email', 'arsalan@example.com')->first();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('registration prevents duplicate emails', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'duplicate@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration validates required fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration validates password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'mismatch@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Different123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
