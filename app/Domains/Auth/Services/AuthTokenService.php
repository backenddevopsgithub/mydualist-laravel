<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTOs\AuthTokenData;
use App\Models\User;
use App\Services\Service;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenService extends Service
{
    public function issue(User $user, string $deviceName = 'api-token'): AuthTokenData
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return new AuthTokenData($user, $token);
    }

    public function revokeCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    public function revokeByPlainTextToken(?string $plainTextToken): void
    {
        if ($plainTextToken === null) {
            return;
        }

        PersonalAccessToken::findToken($plainTextToken)?->delete();
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
