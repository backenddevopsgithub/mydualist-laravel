<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Domains\Auth\Services\AuthTokenService;

class LoginUserAction extends Action
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUserAction,
        private readonly AuthTokenService $authTokenService,
    ) {}

    /**
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
     */
    public function handle(mixed ...$args): mixed
    {
        $credentials = $args[0];
        $user = $this->authenticateUserAction->handle($credentials);

        return $this->authTokenService->issue(
            $user,
            $credentials['device_name'] ?? 'api-token',
        );
    }
}
