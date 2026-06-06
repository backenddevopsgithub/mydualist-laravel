<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->success(
            (new UserResource($request->user()))->resolve(),
            'Authenticated user retrieved.',
        );
    }
}
