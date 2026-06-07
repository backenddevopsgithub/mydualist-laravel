<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Domains\Auth\Services\WordPressPasswordService;
use App\Enums\UserStatus;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Models\User;

class AuthenticateUserAction extends Action
{
    public function __construct(
        private readonly WordPressPasswordService $wordPressPasswordService,
    ) {}

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function handle(mixed ...$args): mixed
    {
        $credentials = $args[0];
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! $this->wordPressPasswordService->verify($credentials['password'], $user)) {
            throw new UnauthorizedException;
        }

        if ($user->status !== UserStatus::Active) {
            throw new ForbiddenException(
                message: 'Your account is not active.',
                errorCode: 'account_inactive',
            );
        }

        if ($user->wp_password_hash !== null) {
            $user = $this->wordPressPasswordService->upgradeFromLegacyHash($user, $credentials['password']);
        }

        return $user;
    }
}
