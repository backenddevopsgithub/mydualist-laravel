<?php

namespace App\Domains\Auth\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function view(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $authUser->role === UserRole::Admin;
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }

    public function accessAdmin(User $authUser): bool
    {
        return $authUser->role === UserRole::Admin;
    }
}
