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
use App\Support\Impersonation;

class UserEntitlementService extends Service
{
    public function __construct(
        private readonly EntitlementGrantService $grants,
        private readonly EntitlementResolverService $resolver,
    ) {}

    public function hasEntitlement(User $user, EntitlementKey|string $key, ?int $duaListId = null): bool
    {
        return $this->grants->hasEntitlement($user, $key, $duaListId);
    }

    public function entitlementQuantity(User $user, EntitlementKey|string $key, ?int $duaListId = null): int
    {
        return $this->grants->quantity($user, $key, $duaListId);
    }

    public function hasPremium(User $user): bool
    {
        return $this->resolver->hasUnlimitedForever($user);
    }

    public function planName(User $user): string
    {
        return $this->hasPremium($user) ? 'Premium' : 'Free';
    }

    public function snapshot(User $user): EntitlementSnapshot
    {
        return $this->resolver->resolveForUser($user);
    }

    public function listSnapshot(User $user, DuaList $duaList): ListEntitlementSnapshot
    {
        return $this->resolver->resolveForList($user, $duaList);
    }

    public function activeListLimit(User $user): ?int
    {
        return $this->resolver->effectiveListCapacity($user);
    }

    public function activeListCount(User $user): int
    {
        return $user->duaLists()->active()->count();
    }

    public function remainingListSlots(User $user): ?int
    {
        $limit = $this->activeListLimit($user);

        return $limit === null ? null : max(0, $limit - $this->activeListCount($user));
    }

    public function canCreateList(User $user): bool
    {
        $limit = $this->activeListLimit($user);

        return $limit === null || $this->activeListCount($user) < $limit;
    }

    public function visibleSubmissionLimit(User $user, DuaList $duaList): ?int
    {
        if ($this->resolver->hasListUnlimitedOverride($user, $duaList)) {
            return null;
        }

        return $this->resolver->effectiveVisibleQuota($user, $duaList);
    }

    public function lockedSubmissionCount(User $user, DuaList $duaList): int
    {
        return $this->resolver->lockedSubmissionCount($user, $duaList);
    }

    public function canViewSubmission(User $user, DuaSubmission $submission): bool
    {
        return $this->resolver->canViewSubmission($user, $submission);
    }

    /**
     * @return list<int>
     */
    public function visibleSubmissionIds(User $user, DuaList $duaList): array
    {
        return $this->resolver->visibleSubmissionIds($user, $duaList);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function grantPremium(User $user, string $source, string $reference, array $metadata = []): UserEntitlement
    {
        Impersonation::ensureSensitiveActionAllowed();

        return UserEntitlement::query()->updateOrCreate(
            [
                'key' => UserEntitlement::KEY_PREMIUM,
                'reference' => $reference,
            ],
            [
                'user_id' => $user->id,
                'active' => true,
                'source' => $source,
                'unlocked_at' => now(),
                'expires_at' => null,
                'metadata' => $metadata,
            ],
        );
    }
}
