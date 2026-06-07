<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('authenticated user can list issued tokens', function () {
    ['token' => $token, 'user' => $user] = $this->registerUser([
        'email' => 'tokens@example.com',
    ]);

    $otherDevice = $user->createToken('ipad');

    $this->withToken($token)
        ->getJson('/api/v1/auth/tokens')
        ->assertOk()
        ->assertJsonPath('message', 'Tokens retrieved.')
        ->assertJsonCount(2, 'data');

    $current = collect($this->getJson('/api/v1/auth/tokens')->json('data'))
        ->firstWhere('is_current', true);

    expect($current)->not->toBeNull()
        ->and($otherDevice->accessToken->id)->not->toBe($current['id']);
});

test('token list requires authentication', function () {
    $this->getJson('/api/v1/auth/tokens')->assertUnauthorized();
});

test('authenticated user can revoke another device token', function () {
    ['token' => $token, 'user' => $user] = $this->registerUser([
        'email' => 'revoke-other@example.com',
    ]);

    $otherDevice = $user->createToken('android');

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/tokens/'.$otherDevice->accessToken->id)
        ->assertOk()
        ->assertJsonPath('message', 'Token revoked successfully.');

    expect($user->tokens()->count())->toBe(1);
});

test('user cannot revoke another users token', function () {
    $owner = User::factory()->create();
    $otherToken = $owner->createToken('owner-device');

    $this->actingAsUser();

    $this->deleteJson('/api/v1/auth/tokens/'.$otherToken->accessToken->id)
        ->assertNotFound();
});

test('revoking current token invalidates subsequent requests', function () {
    ['token' => $token, 'user' => $user] = $this->registerUser([
        'email' => 'revoke-current@example.com',
    ]);

    $currentTokenId = PersonalAccessToken::query()
        ->where('tokenable_id', $user->id)
        ->value('id');

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/tokens/'.$currentTokenId)
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

test('api login is rate limited', function () {
    User::factory()->create([
        'email' => 'api-ratelimit@example.com',
        'password' => 'Password123!',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.40'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'api-ratelimit@example.com',
                'password' => 'wrong-password',
            ])
            ->assertUnauthorized();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.40'])
        ->postJson('/api/v1/auth/login', [
            'email' => 'api-ratelimit@example.com',
            'password' => 'wrong-password',
        ])
        ->assertStatus(429);
});
