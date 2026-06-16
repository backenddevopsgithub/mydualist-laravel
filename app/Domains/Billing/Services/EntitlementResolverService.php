<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Data\EntitlementSnapshot;
use App\Domains\Billing\Data\ListEntitlementSnapshot;
use App\Enums\EntitlementKey;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\Service;

class EntitlementResolverService extends Service
{
    public function __construct(
        private readonly EntitlementGrantService $grants,
    ) {}

    public function hasLegacyPremium(User $user): bool
    {
        return $user->entitlements()
            ->where('key', UserEntitlement::KEY_PREMIUM)
            ->where('active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function hasUnlimitedForever(User $user): bool
    {
        return $this->grants->hasEntitlement($user, EntitlementKey::UserUnlimitedForever)
            || $this->hasLegacyPremium($user);
    }

    public function hasListUnlimitedOverride(User $user, DuaList $duaList): bool
    {
        return $this->hasUnlimitedForever($user)
            || $this->grants->hasEntitlement($user, EntitlementKey::ListUnlimitedOverride, $duaList->id);
    }

    public function effectiveListCapacity(User $user): ?int
    {
        if ($this->hasUnlimitedForever($user)) {
            return null;
        }

        return (int) config('billing.default_list_capacity')
            + $this->grants->quantity($user, EntitlementKey::UserExtraListSlot);
    }

    public function effectiveVisibleQuota(User $user, DuaList $duaList): int
    {
        if ($this->hasListUnlimitedOverride($user, $duaList)) {
            return (int) config('billing.unlimited_list_submission_cap');
        }

        return (int) config('billing.free_visible_submissions_per_list')
            + $this->grants->listScopedQuantity($user, EntitlementKey::ListVisibleSubmissionPack, $duaList->id);
    }

    public function resolveForUser(User $user): EntitlementSnapshot
    {
        $hasUnlimitedForever = $this->hasUnlimitedForever($user);
        $capacity = $this->effectiveListCapacity($user);
        $activeListCount = $user->duaLists()->active()->count();
        $extraListSlots = $this->grants->quantity($user, EntitlementKey::UserExtraListSlot);

        return new EntitlementSnapshot(
            effectiveListCapacity: $capacity ?? PHP_INT_MAX,
            hasUnlimitedListCapacity: $capacity === null,
            extraListSlots: $extraListSlots,
            hasUnlimitedForever: $hasUnlimitedForever,
            hasLegacyPremium: $this->hasLegacyPremium($user),
            activeListCount: $activeListCount,
            remainingListSlots: $capacity === null ? PHP_INT_MAX : max(0, $capacity - $activeListCount),
            canCreateList: $capacity === null || $activeListCount < $capacity,
        );
    }

    public function resolveForList(User $user, DuaList $duaList): ListEntitlementSnapshot
    {
        $quota = $this->effectiveVisibleQuota($user, $duaList);

        return new ListEntitlementSnapshot(
            effectiveVisibleQuota: $quota,
            hasListUnlimitedOverride: $this->grants->hasEntitlement($user, EntitlementKey::ListUnlimitedOverride, $duaList->id),
            hasUnlimitedForever: $this->hasUnlimitedForever($user),
            bonusVisibleSubmissions: $this->grants->listScopedQuantity($user, EntitlementKey::ListVisibleSubmissionPack, $duaList->id),
            lockedSubmissionCount: $this->lockedSubmissionCount($user, $duaList, $quota),
        );
    }

    public function lockedSubmissionCount(User $user, DuaList $duaList, ?int $quota = null): int
    {
        $quota ??= $this->effectiveVisibleQuota($user, $duaList);

        $persistedLocked = $duaList->submissions()
            ->where('is_personal_dua', false)
            ->where('is_locked', true)
            ->whereNull('unlocked_at')
            ->count();

        if ($persistedLocked > 0) {
            return $persistedLocked;
        }

        $total = $duaList->submissions()
            ->where('is_personal_dua', false)
            ->count();

        return max(0, $total - $quota);
    }

    public function canViewSubmission(User $user, DuaSubmission $submission): bool
    {
        if ($submission->isPersonalDua() && $submission->duaList->user_id === $user->id) {
            return true;
        }

        if ($submission->unlocked_at !== null) {
            return true;
        }

        if ($submission->is_locked) {
            return false;
        }

        $quota = $this->effectiveVisibleQuota($user, $submission->duaList);

        $rank = DuaSubmission::query()
            ->where('dua_list_id', $submission->dua_list_id)
            ->where('is_personal_dua', false)
            ->where('id', '<=', $submission->id)
            ->count();

        return $rank <= $quota;
    }

    /**
     * @return list<int>
     */
    public function visibleSubmissionIds(User $user, DuaList $duaList): array
    {
        $quota = $this->effectiveVisibleQuota($user, $duaList);
        $hasUnlimited = $this->hasListUnlimitedOverride($user, $duaList);

        $personalIds = $duaList->submissions()
            ->where('is_personal_dua', true)
            ->pluck('id');

        if ($hasUnlimited) {
            $regularIds = $duaList->submissions()
                ->where('is_personal_dua', false)
                ->where(function ($query): void {
                    $query->where('is_locked', false)
                        ->orWhereNotNull('unlocked_at');
                })
                ->pluck('id');

            return $personalIds->merge($regularIds)->all();
        }

        $unlockedIds = $duaList->submissions()
            ->where('is_personal_dua', false)
            ->whereNotNull('unlocked_at')
            ->pluck('id');

        $regularIds = $duaList->submissions()
            ->where('is_personal_dua', false)
            ->where('is_locked', false)
            ->whereNull('unlocked_at')
            ->oldest('id')
            ->limit($quota)
            ->pluck('id');

        return $personalIds->merge($unlockedIds)->merge($regularIds)->unique()->values()->all();
    }
}
