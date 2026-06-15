<?php

namespace App\Domains\Community\Services;

use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Models\CommunityDuaQueueState;
use App\Models\CommunityDuaSkip;
use App\Models\DuaList;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Collection;

class CommunityDuaEligibilityService extends Service
{
    public function shouldShowForList(User $user, DuaList $duaList): bool
    {
        if ($duaList->user_id !== $user->id) {
            return false;
        }

        if (! $duaList->isActive() || $duaList->isExpired()) {
            return false;
        }

        if ($duaList->submissions()->where('status', 'pending')->exists()) {
            return false;
        }

        return $duaList->submissions()->exists();
    }
}
