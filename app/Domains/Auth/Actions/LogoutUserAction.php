<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Domains\Auth\Services\AuthTokenService;
use App\Models\User;

class LogoutUserAction extends Action
{
    public function __construct(
        private readonly AuthTokenService $authTokenService,
    ) {
    }

    public function handle(mixed ...$args): mixed
    {
        $user = $args[0];
        $plainTextToken = $args[1] ?? null;

        if ($plainTextToken !== null) {
            $this->authTokenService->revokeByPlainTextToken($plainTextToken);
        } else {
            $this->authTokenService->revokeCurrent($user);
        }

        return null;
    }
}
