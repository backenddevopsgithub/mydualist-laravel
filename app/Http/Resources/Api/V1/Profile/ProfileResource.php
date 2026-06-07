<?php

namespace App\Http\Resources\Api\V1\Profile;

use App\Http\Resources\Api\V1\ApiResource;
use App\Http\Resources\Api\V1\Billing\EntitlementResource;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Http\Request;

/** @mixin User */
class ProfileResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ownedListIds = $this->duaLists()->pluck('id');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'entitlements' => (new EntitlementResource($this->resource))->resolve(),
            'stats' => [
                'active_lists_count' => $this->duaLists()->active()->count(),
                'archived_lists_count' => $this->duaLists()->archived()->count(),
                'total_submissions_count' => DuaSubmission::query()->whereIn('dua_list_id', $ownedListIds)->count(),
                'completed_duas_count' => DuaSubmission::query()
                    ->whereIn('dua_list_id', $ownedListIds)
                    ->where('status', DuaSubmission::STATUS_COMPLETED)
                    ->count(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
