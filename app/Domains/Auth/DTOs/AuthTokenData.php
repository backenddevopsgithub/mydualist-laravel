<?php

namespace App\Domains\Auth\DTOs;

use App\Models\User;

readonly class AuthTokenData
{
    public function __construct(
        public User $user,
        public string $token,
        public string $tokenType = 'Bearer',
    ) {
    }
}
