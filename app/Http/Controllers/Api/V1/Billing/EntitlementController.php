<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Billing\EntitlementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->success(
            (new EntitlementResource($request->user()))->resolve(),
            'Entitlements retrieved.',
        );
    }
}
