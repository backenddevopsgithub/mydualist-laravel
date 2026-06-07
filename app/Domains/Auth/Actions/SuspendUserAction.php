<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Enums\UserStatus;
use App\Models\User;

class SuspendUserAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];

        $user->forceFill([
            'status' => UserStatus::Suspended,
        ])->save();

        return $user->fresh();
    }
}
