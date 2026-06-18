<?php

namespace App\Policies;

use App\Models\AdminExport;
use App\Models\User;

class AdminExportPolicy
{
    public function download(User $user, AdminExport $export): bool
    {
        if ($user->id !== $export->user_id) {
            return false;
        }

        if ($export->type->isUserFacing()) {
            return $user->hasVerifiedEmail();
        }

        return $user->isAdmin() && $user->isActive();
    }
}
