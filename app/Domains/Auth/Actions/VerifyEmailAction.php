<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Exceptions\DomainException;
use App\Models\User;

class VerifyEmailAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        $user = $args[0];
        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email address is already verified.', 'email_already_verified');
        }

        $user->markEmailAsVerified();

        return $user->fresh();
    }
}
