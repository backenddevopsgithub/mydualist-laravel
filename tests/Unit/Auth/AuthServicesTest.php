<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domains\Auth\Services\AuthTokenService;
use App\Domains\Auth\Services\WordPressPasswordService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('wordpress password service verifies legacy hash', function () {
    $user = User::factory()->withWordPressPassword('legacy-password')->create();

    $service = app(WordPressPasswordService::class);

    expect($service->verify('legacy-password', $user))->toBeTrue()
        ->and($service->verify('wrong-password', $user))->toBeFalse();
});

test('wordpress password service upgrades legacy hash to bcrypt', function () {
    $user = User::factory()->withWordPressPassword('legacy-password')->create();

    $service = app(WordPressPasswordService::class);
    $upgraded = $service->upgradeFromLegacyHash($user, 'legacy-password');

    expect($upgraded->wp_password_hash)->toBeNull()
        ->and(Hash::check('legacy-password', $upgraded->password))->toBeTrue();
});

test('login upgrades wordpress password hash', function () {
    User::factory()->withWordPressPassword('legacy-password')->create([
        'email' => 'legacy@example.com',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'legacy@example.com',
        'password' => 'legacy-password',
    ])->assertOk();

    $user = User::query()->where('email', 'legacy@example.com')->first();

    expect($user->wp_password_hash)->toBeNull()
        ->and(Hash::check('legacy-password', $user->password))->toBeTrue();
});

test('auth token service issues and revokes tokens', function () {
    $user = User::factory()->create();
    $service = app(AuthTokenService::class);

    $authToken = $service->issue($user, 'test-device');

    expect($authToken->token)->not->toBeEmpty()
        ->and($user->tokens()->count())->toBe(1);

    $user->withAccessToken($user->tokens()->first());
    $service->revokeCurrent($user);

    expect($user->tokens()->count())->toBe(0);
});
