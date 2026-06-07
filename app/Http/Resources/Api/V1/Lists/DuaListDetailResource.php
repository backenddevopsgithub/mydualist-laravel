<?php

namespace App\Http\Resources\Api\V1\Lists;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\DuaList;
use Illuminate\Http\Request;

/** @mixin DuaList */
class DuaListDetailResource extends DuaListResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $entitlements = app(UserEntitlementService::class);

        return [
            ...parent::toArray($request),
            'dua_limit_per_person' => $this->dua_limit_per_person,
            'display_order' => $this->display_order,
            'email_frequency' => $this->email_frequency,
            'entitlements' => $user ? [
                'has_premium' => $entitlements->hasPremium($user),
                'visible_submission_limit' => $entitlements->visibleSubmissionLimit($user, $this->resource),
                'locked_submission_count' => $entitlements->lockedSubmissionCount($user, $this->resource),
            ] : null,
        ];
    }
}
