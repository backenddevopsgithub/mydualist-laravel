<?php

namespace App\Policies;

use App\Models\User;

class QueueMonitorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    public function retryFailedJob(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function retryAllFailedJobs(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function flushFailedJobs(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
