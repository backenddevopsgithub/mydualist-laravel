<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Http\Resources\Api\V1\ApiResource;
use App\Models\User;
use Illuminate\Http\Request;

/** @mixin User */
class EntitlementResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entitlements = app(UserEntitlementService::class);

        return [
            'plan' => $entitlements->planName($this->resource),
            'has_premium' => $entitlements->hasPremium($this->resource),
            'active_list_limit' => $entitlements->activeListLimit($this->resource),
            'active_list_count' => $entitlements->activeListCount($this->resource),
            'remaining_list_slots' => $entitlements->remainingListSlots($this->resource),
            'can_create_list' => $entitlements->canCreateList($this->resource),
            'free_visible_submissions_per_list' => (int) config('mydualist.billing.free_visible_submissions_per_list', 25),
        ];
    }
}
