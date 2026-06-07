<?php

namespace App\Policies;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

class DuaSubmissionPolicy
{
    public function viewAny(User $user, DuaList $duaList): bool
    {
        return $duaList->user_id === $user->id || $user->isAdmin();
    }

    public function manage(User $user, DuaSubmission $submission): bool
    {
        if ($submission->duaList->user_id !== $user->id && ! $user->isAdmin()) {
            return false;
        }

        return app(UserEntitlementService::class)->canViewSubmission($user, $submission);
    }

    public function report(User $user, DuaSubmission $submission): bool
    {
        return $this->manage($user, $submission);
    }
}
