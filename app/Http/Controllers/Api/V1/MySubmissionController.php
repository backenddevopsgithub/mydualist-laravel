<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Http\Requests\Api\V1\Submissions\IndexMySubmissionRequest;
use App\Http\Resources\Api\V1\Submissions\DuaSubmissionResource;
use Illuminate\Http\JsonResponse;

class MySubmissionController extends ApiController
{
    public function index(IndexMySubmissionRequest $request, DuaSubmissionQueryService $submissions): JsonResponse
    {
        $paginator = $submissions->paginateForUser(
            $request->user(),
            $request->perPage(),
            ['duaList:id,title,slug'],
        );

        return $this->paginated($paginator, DuaSubmissionResource::class, 'Submissions retrieved.');
    }
}
