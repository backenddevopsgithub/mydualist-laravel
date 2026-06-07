<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Auth\Services\AuthTokenService;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Auth\PersonalAccessTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends ApiController
{
    public function index(Request $request, AuthTokenService $tokens): JsonResponse
    {
        $items = PersonalAccessTokenResource::collection($tokens->listForUser($request->user()))
            ->resolve();

        return $this->success($items, 'Tokens retrieved.');
    }

    public function destroy(Request $request, int $token, AuthTokenService $tokens): JsonResponse
    {
        $tokens->revokeById($request->user(), $token);

        return $this->success(null, 'Token revoked successfully.');
    }
}
