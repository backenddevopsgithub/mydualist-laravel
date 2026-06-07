<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Models\User;

class ResetEmailVerificationAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];

        $user->forceFill([
            'email_verified_at' => null,
        ])->save();

        return $user->fresh();
    }
}
