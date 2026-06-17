<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domains\Cms\Services\DuaSuggestionService;
use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Security\Services\PublicSubmissionSpamGuard;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Submissions\StorePublicSubmissionRequest;
use App\Http\Resources\Api\V1\Public\PublicSubmissionResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PublicSubmissionController extends ApiController
{
    public function store(
        StorePublicSubmissionRequest $request,
        string $slug,
        DuaListQueryService $lists,
        CreateDuaSubmissionAction $action,
        PublicSubmissionSpamGuard $spamGuard,
        DuaSuggestionService $suggestions,
    ): JsonResponse {
        $duaList = $lists->findPublicBySlug($slug);

        if (! $duaList->acceptsSubmissions()) {
            throw ValidationException::withMessages([
                'content' => $duaList->closedReason() ?? 'This list is not accepting submissions.',
            ]);
        }

        $data = $request->validated();

        $spamGuard->inspect($duaList, $data, $request->ip());

        $submissions = $action($duaList, $data, $request->user());
        $suggestions->incrementUsedCounts($data['suggestion_ids'] ?? []);
        $result = (new PublicSubmissionResultResource($submissions))->resolve();

        return $this->success($result, $result['message'], 201);
    }
}
