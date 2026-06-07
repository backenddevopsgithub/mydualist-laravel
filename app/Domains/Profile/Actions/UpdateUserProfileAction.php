<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Models\User;

class UpdateUserProfileAction extends Action
{
    /**
     * @param  array{first_name: string, last_name: string, email: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];

        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
        ])->save();

        return $user->fresh();
    }
}
