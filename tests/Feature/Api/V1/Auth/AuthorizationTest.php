<?php

use App\Models\User;

test('guest cannot access admin route', function () {
    $this->getJson('/api/v1/admin/ping')->assertUnauthorized();
});

test('regular user cannot access admin route', function () {
    $this->actingAsUser();

    $this->getJson('/api/v1/admin/ping')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'insufficient_role');
});

test('admin user can access admin route', function () {
    $this->actingAsAdmin();

    $this->getJson('/api/v1/admin/ping')
        ->assertOk()
        ->assertJsonPath('data.scope', 'admin');
});

test('verified middleware blocks unverified users', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->getJson('/api/v1/auth/verified-check')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'email_not_verified');
});

test('verified middleware allows verified users', function () {
    $this->actingAsUser();

    $this->getJson('/api/v1/auth/verified-check')
        ->assertOk()
        ->assertJsonPath('message', 'Verified access granted.');
});

test('user policy allows self view and denies other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->can('view', $user))->toBeTrue()
        ->and($user->can('view', $other))->toBeFalse();
});

test('admin policy allows viewing other users', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    expect($admin->can('view', $other))->toBeTrue()
        ->and($admin->can('accessAdmin', $admin))->toBeTrue();
});
