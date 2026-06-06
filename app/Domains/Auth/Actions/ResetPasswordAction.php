<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction extends Action
{
    /**
     * @param  array{email: string, token: string, password: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];
        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password'],
                'token' => $data['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'wp_password_hash' => null,
                    'remember_token' => null,
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new DomainException(
                message: __($status),
                errorCode: 'invalid_reset_token',
            );
        }

        return User::query()->where('email', $data['email'])->firstOrFail();
    }
}
