<?php

namespace App\Domains\Billing\Fulfillment;

use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;

class SubmissionUnlockService extends Service
{
    public function unlockEligibleForList(BillingPurchase $purchase, int $duaListId, ?int $limit = null): int
    {
        $query = DuaSubmission::query()
            ->where('dua_list_id', $duaListId)
            ->where('is_personal_dua', false)
            ->quotaLocked()
            ->oldest('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return 0;
        }

        return DuaSubmission::query()
            ->whereIn('id', $submissionIds)
            ->update([
                'unlocked_at' => now(),
                'unlock_purchase_id' => $purchase->id,
                'is_locked' => false,
                'locked_reason' => null,
                'locked_at_quota' => null,
            ]);
    }

    public function unlockEligibleForUserLists(BillingPurchase $purchase, User $user): int
    {
        $unlocked = 0;

        DuaList::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->each(function (int $duaListId) use ($purchase, &$unlocked): void {
                $unlocked += $this->unlockEligibleForList($purchase, $duaListId);
            });

        return $unlocked;
    }
}
