<?php

namespace App\Policies;

use App\Models\EntitlementGrant;
use App\Models\User;
use App\Support\Impersonation;

class EntitlementGrantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, EntitlementGrant $grant): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && ! Impersonation::isActive();
    }

    public function update(User $user, EntitlementGrant $grant): bool
    {
        return $user->isAdmin() && ! Impersonation::isActive();
    }

    public function delete(User $user, EntitlementGrant $grant): bool
    {
        return false;
    }
}
