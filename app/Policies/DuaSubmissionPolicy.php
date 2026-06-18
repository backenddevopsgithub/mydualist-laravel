<?php

namespace App\Policies;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

class DuaSubmissionPolicy
{
    public function viewAny(User $user, ?DuaList $duaList = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $duaList !== null && $duaList->user_id === $user->id;
    }

    public function manage(User $user, DuaSubmission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($submission->duaList->user_id !== $user->id && ! $user->isAdmin()) {
            return false;
        }

        $submission->loadMissing('duaList');

        return app(UserEntitlementService::class)->canViewSubmission($user, $submission);
    }

    public function report(User $user, DuaSubmission $submission): bool
    {
        return $this->manage($user, $submission);
    }

    public function moderateAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user, DuaSubmission $submission): bool
    {
        return $user->isAdmin();
    }
}
