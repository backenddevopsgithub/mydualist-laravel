<?php

namespace App\Policies;

use App\Models\AdminExport;
use App\Models\User;

class AdminExportPolicy
{
    public function download(User $user, AdminExport $export): bool
    {
        return $user->isAdmin() && $user->isActive() && $user->id === $export->user_id;
    }
}
