<?php

namespace App\Domains\Billing\Services;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\Service;

class UserEntitlementService extends Service
{
    public function hasPremium(User $user): bool
    {
        return $user->entitlements()
            ->where('key', UserEntitlement::KEY_PREMIUM)
            ->where('active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function planName(User $user): string
    {
        return $this->hasPremium($user) ? 'Premium' : 'Free';
    }

    public function activeListLimit(User $user): ?int
    {
        return $this->hasPremium($user) ? null : (int) config('mydualist.billing.free_list_limit', 2);
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
        return $this->hasPremium($user) ? null : (int) config('mydualist.billing.free_visible_submissions_per_list', 25);
    }

    public function lockedSubmissionCount(User $user, DuaList $duaList): int
    {
        $limit = $this->visibleSubmissionLimit($user, $duaList);

        if ($limit === null) {
            return 0;
        }

        return max(0, $duaList->submissions()->where('is_personal_dua', false)->count() - $limit);
    }

    public function canViewSubmission(User $user, DuaSubmission $submission): bool
    {
        if ($submission->is_personal_dua && $submission->duaList->user_id === $user->id) {
            return true;
        }

        $limit = $this->visibleSubmissionLimit($user, $submission->duaList);

        if ($limit === null) {
            return true;
        }

        $rank = DuaSubmission::query()
            ->where('dua_list_id', $submission->dua_list_id)
            ->where('is_personal_dua', false)
            ->where('id', '<=', $submission->id)
            ->count();

        return $rank <= $limit;
    }

    /**
     * @return list<int>
     */
    public function visibleSubmissionIds(User $user, DuaList $duaList): array
    {
        $limit = $this->visibleSubmissionLimit($user, $duaList);

        if ($limit === null) {
            return $duaList->submissions()->pluck('id')->all();
        }

        $personalIds = $duaList->submissions()
            ->where('is_personal_dua', true)
            ->pluck('id');

        $regularIds = $duaList->submissions()
            ->where('is_personal_dua', false)
            ->oldest('id')
            ->limit($limit)
            ->pluck('id');

        return $personalIds->merge($regularIds)->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function grantPremium(User $user, string $source, string $reference, array $metadata = []): UserEntitlement
    {
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
