<?php

namespace App\Domains\Submissions\Services;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;
use App\Support\DuaListDisplayOrders;
use Illuminate\Database\Eloquent\Builder;

class DuaSubmissionOrderingService extends Service
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    /**
     * @param  Builder<DuaSubmission>  $query
     * @return Builder<DuaSubmission>
     */
    public function applyOwnerListOrdering(Builder $query, DuaList $duaList, User $owner): Builder
    {
        $this->applyLockedPartition($query, $duaList, $owner);
        $this->applyDisplayOrder($query, $duaList);

        return $query;
    }

    /**
     * @param  Builder<DuaSubmission>  $query
     */
    private function applyLockedPartition(Builder $query, DuaList $duaList, User $owner): void
    {
        if ($this->entitlements->visibleSubmissionLimit($owner, $duaList) === null) {
            return;
        }

        $query->orderByRaw(
            'CASE WHEN is_personal_dua = 1 OR unlocked_at IS NOT NULL OR is_locked = 0 THEN 1 ELSE 0 END ASC',
        );
    }

    /**
     * @param  Builder<DuaSubmission>  $query
     */
    private function applyDisplayOrder(Builder $query, DuaList $duaList): void
    {
        match ($duaList->display_order ?? DuaListDisplayOrders::DEFAULT) {
            DuaListDisplayOrders::GENDER => $query
                ->orderByRaw("CASE WHEN gender = 'male' THEN 0 WHEN gender = 'female' THEN 1 ELSE 2 END")
                ->orderBy('id'),
            DuaListDisplayOrders::PERSON => $query
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->orderBy('id'),
            default => $query->orderBy('id'),
        };
    }
}
