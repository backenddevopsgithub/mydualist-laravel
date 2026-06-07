<?php

use App\Models\UserEntitlement;

test('entitlements endpoint requires authentication', function () {
    $this->getJson('/api/v1/billing/entitlements')->assertUnauthorized();
});

test('authenticated user can fetch entitlement summary', function () {
    $user = $this->actingAsUser();

    $this->getJson('/api/v1/billing/entitlements')
        ->assertOk()
        ->assertJsonPath('message', 'Entitlements retrieved.')
        ->assertJsonPath('data.plan', 'Free')
        ->assertJsonPath('data.has_premium', false)
        ->assertJsonPath('data.active_list_limit', 2)
        ->assertJsonPath('data.can_create_list', true)
        ->assertJsonPath('data.free_visible_submissions_per_list', 25);
});

test('premium user entitlement payload reflects premium plan', function () {
    $user = $this->actingAsUser();

    UserEntitlement::query()->create([
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'active' => true,
        'source' => 'test',
        'reference' => 'test-premium',
        'unlocked_at' => now(),
    ]);

    $this->getJson('/api/v1/billing/entitlements')
        ->assertOk()
        ->assertJsonPath('data.plan', 'Premium')
        ->assertJsonPath('data.has_premium', true)
        ->assertJsonPath('data.active_list_limit', null);
});
