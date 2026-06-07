<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Enums\UserStatus;
use App\Models\User;

class ActivateUserAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];

        $user->forceFill([
            'status' => UserStatus::Active,
        ])->save();

        return $user->fresh();
    }
}
