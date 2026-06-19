<?php

namespace App\Domains\Billing\Services;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;

class ListSubmissionQuotaService extends Service
{
    public function __construct(
        private readonly EntitlementResolverService $entitlements,
    ) {}

    public function effectiveVisibleQuota(User $user, DuaList $list): int
    {
        return $this->entitlements->effectiveVisibleQuota($user, $list);
    }

    public function hasUnlimitedOverride(User $user, DuaList $list): bool
    {
        return $this->entitlements->hasListUnlimitedOverride($user, $list);
    }

    public function unlockedRegularSubmissionCount(DuaList $list): int
    {
        return DuaSubmission::query()
            ->where('dua_list_id', $list->id)
            ->where('is_personal_dua', false)
            ->where('is_locked', false)
            ->count();
    }

    public function visibleExceedsQuota(User $user, DuaList $list): bool
    {
        if ($this->hasUnlimitedOverride($user, $list)) {
            return false;
        }

        return $this->unlockedRegularSubmissionCount($list) > $this->effectiveVisibleQuota($user, $list);
    }

    /**
     * @return array{visible: int, quota: int, exceeds: bool}
     */
    public function inspect(User $user, DuaList $list): array
    {
        $quota = $this->effectiveVisibleQuota($user, $list);
        $visible = $this->unlockedRegularSubmissionCount($list);

        return [
            'visible' => $visible,
            'quota' => $quota,
            'exceeds' => ! $this->hasUnlimitedOverride($user, $list) && $visible > $quota,
        ];
    }
}
