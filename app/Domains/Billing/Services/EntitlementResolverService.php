<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Data\EntitlementSnapshot;
use App\Domains\Billing\Data\ListEntitlementSnapshot;
use App\Enums\EntitlementKey;
use App\Enums\SubmissionLockReason;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\Service;
use App\Support\MemoizesPerRequest;

class EntitlementResolverService extends Service
{
    use MemoizesPerRequest;

    public function __construct(
        private readonly EntitlementGrantService $grants,
    ) {}

    public function hasLegacyPremium(User $user): bool
    {
        return $this->memo(
            "legacyPremium:{$user->id}",
            fn (): bool => $user->entitlements()
                ->where('key', UserEntitlement::KEY_PREMIUM)
                ->where('active', true)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists(),
        );
    }

    public function hasUnlimitedForever(User $user): bool
    {
        return $this->memo(
            "unlimitedForever:{$user->id}",
            fn (): bool => $this->grants->hasEntitlement($user, EntitlementKey::UserUnlimitedForever)
                || $this->hasLegacyPremium($user),
        );
    }

    public function hasListUnlimitedOverride(User $user, DuaList $duaList): bool
    {
        return $this->memo(
            "listUnlimitedOverride:{$user->id}:{$duaList->id}",
            fn (): bool => $this->hasUnlimitedForever($user)
                || $this->grants->hasEntitlement($user, EntitlementKey::ListUnlimitedOverride, $duaList->id),
        );
    }

    public function effectiveListCapacity(User $user): ?int
    {
        return $this->memo(
            "effectiveListCapacity:{$user->id}",
            function () use ($user): ?int {
                if ($this->hasUnlimitedForever($user)) {
                    return null;
                }

                return (int) config('billing.default_list_capacity')
                    + $this->grants->quantity($user, EntitlementKey::UserExtraListSlot);
            },
        );
    }

    public function effectiveVisibleQuota(User $user, DuaList $duaList): int
    {
        return $this->memo(
            "effectiveVisibleQuota:{$user->id}:{$duaList->id}",
            function () use ($user, $duaList): int {
                if ($this->hasListUnlimitedOverride($user, $duaList)) {
                    return (int) config('billing.unlimited_list_submission_cap');
                }

                return (int) config('billing.free_visible_submissions_per_list')
                    + $this->grants->listScopedQuantity($user, EntitlementKey::ListVisibleSubmissionPack, $duaList->id);
            },
        );
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
        return $this->memo(
            "lockedSubmissionCount:{$user->id}:{$duaList->id}",
            function () use ($user, $duaList): int {
                if ($this->hasListUnlimitedOverride($user, $duaList)) {
                    return 0;
                }

                return $duaList->submissions()
                    ->where('is_personal_dua', false)
                    ->quotaLocked()
                    ->count();
            },
        );
    }

    public function canViewSubmission(User $user, DuaSubmission $submission): bool
    {
        $submission->loadMissing('duaList');

        if ($submission->isPersonalDua() && $submission->duaList->user_id === $user->id) {
            return true;
        }

        if ($submission->unlocked_at !== null) {
            return true;
        }

        if ($this->hasListUnlimitedOverride($user, $submission->duaList)) {
            return true;
        }

        return ! $submission->isQuotaLocked();
    }

    /**
     * @return array{is_locked: bool, locked_reason: ?SubmissionLockReason, locked_at_quota: ?int}
     */
    public function lockAttributesForNewRegularSubmission(User $user, DuaList $duaList, int $regularRank): array
    {
        if ($this->hasListUnlimitedOverride($user, $duaList)) {
            return [
                'is_locked' => false,
                'locked_reason' => null,
                'locked_at_quota' => null,
            ];
        }

        $quota = $this->effectiveVisibleQuota($user, $duaList);
        $shouldLock = $regularRank > $quota;

        return [
            'is_locked' => $shouldLock,
            'locked_reason' => $shouldLock ? SubmissionLockReason::VisibleQuotaExhausted : null,
            'locked_at_quota' => $shouldLock ? $quota : null,
        ];
    }

    public function submissionIsLockedForOwner(DuaSubmission $submission, User $user, DuaList $duaList): bool
    {
        if ($submission->isPersonalDua()) {
            return false;
        }

        if ($this->hasListUnlimitedOverride($user, $duaList)) {
            return false;
        }

        return $submission->isQuotaLocked();
    }
}
