<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangeUserPasswordAction extends Action
{
    /**
     * @param  array{password: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'wp_password_hash' => null,
        ])->save();

        return $user->fresh();
    }
}
