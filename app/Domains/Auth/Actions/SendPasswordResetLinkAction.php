<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction extends Action
{
    /**
     * Always returns a generic success status to avoid email enumeration.
     *
     * @param  array{email: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];

        Password::broker()->sendResetLink([
            'email' => $data['email'],
        ]);

        return Password::RESET_LINK_SENT;
    }
}
