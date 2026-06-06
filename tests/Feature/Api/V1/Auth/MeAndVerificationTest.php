<?php

use App\Domains\Auth\Notifications\VerifyEmailNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('authenticated user can fetch current profile', function () {
    $user = $this->actingAsUser();

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('me endpoint requires authentication', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('user resource does not expose sensitive fields', function () {
    $user = User::factory()->withWordPressPassword()->create([
        'wp_legacy_id' => 123,
    ]);

    $this->actingAsUser($user);

    $response = $this->getJson('/api/v1/auth/me')->assertOk();

    expect($response->json('data'))->not->toHaveKeys(['password', 'wp_password_hash', 'remember_token']);
});

test('email can be verified via signed url', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.email.verify',
        now()->addHour(),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ],
    );

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email verification rejects invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.v1.auth.email.verify',
        now()->addHour(),
        [
            'id' => $user->id,
            'hash' => sha1('wrong@example.com'),
        ],
    );

    $this->getJson($url)->assertStatus(422)
        ->assertJsonPath('error_code', 'invalid_verification_link');
});

test('authenticated user can resend verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'Verification link sent.');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('resend verification fails when already verified', function () {
    $user = User::factory()->create();
    $this->actingAsUser($user);

    $this->postJson('/api/v1/auth/email/verification-notification')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'email_already_verified');
});
