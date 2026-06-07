<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** @mixin PersonalAccessToken */
class PersonalAccessTokenResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentToken = $request->user()?->currentAccessToken();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => $this->abilities,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_current' => $currentToken !== null && $currentToken->id === $this->id,
        ];
    }
}
