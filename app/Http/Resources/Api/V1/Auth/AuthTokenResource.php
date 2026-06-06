<?php

namespace App\Http\Resources\Api\V1\Auth;

use App\Domains\Auth\DTOs\AuthTokenData;
use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

/** @mixin AuthTokenData */
class AuthTokenResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuthTokenData $authToken */
        $authToken = $this->resource;

        return [
            'user' => new UserResource($authToken->user),
            'token' => $authToken->token,
            'token_type' => $authToken->tokenType,
        ];
    }
}
