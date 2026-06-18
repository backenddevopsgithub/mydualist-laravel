<?php

namespace App\Http\Resources\Api\V1\Profile;

use App\Domains\Lists\Services\DuaListQueryService;
use App\Http\Resources\Api\V1\ApiResource;
use App\Http\Resources\Api\V1\Billing\EntitlementResource;
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
            'stats' => app(DuaListQueryService::class)->dashboardSummary($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
