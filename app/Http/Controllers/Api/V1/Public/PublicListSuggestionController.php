<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Cms\Services\DuaSuggestionService;
use App\Domains\Lists\Services\DuaListQueryService;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\Public\PublicDuaSuggestionResource;
use Illuminate\Http\JsonResponse;

class PublicListSuggestionController extends ApiController
{
    public function index(
        string $slug,
        DuaListQueryService $lists,
        DuaSuggestionService $suggestions,
    ): JsonResponse {
        $duaList = $lists->findPublicBySlug($slug);

        $grouped = $suggestions->getForList($duaList);

        $data = $grouped->map(
            fn ($items) => PublicDuaSuggestionResource::collection($items)->resolve(),
        )->all();

        return $this->success($data, 'List suggestions retrieved.');
    }
}
