<?php

namespace App\Policies;

use App\Models\User;

class AnalyticsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }
}
