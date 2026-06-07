<?php

use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('frontend auth pages render', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Welcome Back', false)
        ->assertSee('Sign In');

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Reset your', false)
        ->assertSee('Send reset link');

    $this->get('/reset-password?token=test-token&email=user@example.com')
        ->assertOk()
        ->assertSee('Create New Password')
        ->assertSee('Reset password');
});

test('homepage sign in links point to frontend login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('/login"', false)
        ->assertDontSee('/admin/login"', false);
});

test('user can login with frontend session form', function () {
    $user = User::factory()->create([
        'email' => 'web-login@example.com',
        'password' => 'Password123!',
    ]);

    $this->post('/login', [
        'email' => 'web-login@example.com',
        'password' => 'Password123!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('frontend login validates credentials', function () {
    User::factory()->create([
        'email' => 'web-login@example.com',
        'password' => 'Password123!',
    ]);

    $this->from('/login')
        ->post('/login', [
            'email' => 'web-login@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');
});

test('frontend login rejects inactive users', function () {
    User::factory()->create([
        'email' => 'inactive-web@example.com',
        'password' => 'Password123!',
        'status' => UserStatus::Suspended,
    ]);

    $this->from('/login')
        ->post('/login', [
            'email' => 'inactive-web@example.com',
            'password' => 'Password123!',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');
});

test('forgot password page sends reset link through existing notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset-web@example.com']);

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => 'reset-web@example.com'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password page updates password and redirects to login', function () {
    $user = User::factory()->withWordPressPassword()->create([
        'email' => 'reset-web@example.com',
    ]);

    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'email' => 'reset-web@example.com',
        'token' => $token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $fresh = $user->fresh();

    expect(Hash::check('NewPassword123!', $fresh->password))->toBeTrue()
        ->and($fresh->wp_password_hash)->toBeNull();
});

test('reset password page validates form state', function () {
    $this->from('/reset-password')
        ->post('/reset-password', [])
        ->assertRedirect('/reset-password')
        ->assertSessionHasErrors(['email', 'token', 'password']);
});
