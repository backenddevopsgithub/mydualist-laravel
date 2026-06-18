<?php

namespace App\Policies;

use App\Models\User;

class MediaLibraryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }
}
