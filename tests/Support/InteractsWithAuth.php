<?php

namespace Tests\Support;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait InteractsWithAuth
{
    protected function actingAsUser(?User $user = null, array $abilities = ['*']): User
    {
        $user ??= User::factory()->create();

        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    protected function actingAsAdmin(?User $user = null): User
    {
        $user ??= User::factory()->admin()->create();

        return $this->actingAsUser($user);
    }

    /**
     * @return array{token: string, user: User}
     */
    protected function registerUser(array $overrides = []): array
    {
        $payload = array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'pest-tests',
        ], $overrides);

        $response = $this->postJson('/api/v1/auth/register', $payload);
        $response->assertCreated();

        return [
            'token' => $response->json('data.token'),
            'user' => User::query()->where('email', $payload['email'])->firstOrFail(),
        ];
    }
}
