<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Lists\Services\DuaListQueryService;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Lists\PublicDuaListResource;
use Illuminate\Http\JsonResponse;

class PublicListController extends ApiController
{
    public function show(string $slug, DuaListQueryService $lists): JsonResponse
    {
        $duaList = $lists->findPublicBySlug($slug);

        return $this->success(
            (new PublicDuaListResource($duaList))->resolve(),
            'Public list retrieved.',
        );
    }
}
