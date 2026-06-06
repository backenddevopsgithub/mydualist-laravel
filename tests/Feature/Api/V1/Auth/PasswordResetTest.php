<?php

use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password sends reset notification for existing user', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'reset@example.com',
    ])->assertOk();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('forgot password returns generic success for unknown email', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'unknown@example.com',
    ])->assertOk();

    Notification::assertNothingSent();
});

test('forgot password validates email', function () {
    $this->postJson('/api/v1/auth/forgot-password', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('user can reset password with valid token', function () {
    $user = User::factory()->withWordPressPassword()->create([
        'email' => 'reset@example.com',
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'reset@example.com',
        'token' => $token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Password reset successful.');

    $fresh = $user->fresh();

    expect(Hash::check('NewPassword123!', $fresh->password))->toBeTrue()
        ->and($fresh->wp_password_hash)->toBeNull();
});

test('reset password rejects invalid token', function () {
    User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'reset@example.com',
        'token' => 'invalid-token',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(422)
        ->assertJsonPath('error_code', 'invalid_reset_token');
});

test('reset password validates required fields', function () {
    $this->postJson('/api/v1/auth/reset-password', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'token', 'password']);
});
