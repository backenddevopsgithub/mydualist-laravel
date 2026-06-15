<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Events\UserEmailVerified;
use App\Exceptions\DomainException;

class VerifyEmailAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        $user = $args[0];
        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email address is already verified.', 'email_already_verified');
        }

        $user->markEmailAsVerified();

        event(new UserEmailVerified($user->fresh()));

        return $user->fresh();
    }
}
