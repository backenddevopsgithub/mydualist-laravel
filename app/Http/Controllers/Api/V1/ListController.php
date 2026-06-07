<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Lists\Actions\ArchiveDuaListAction;
use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Domains\Lists\Actions\DeleteDuaListAction;
use App\Domains\Lists\Actions\RestoreDuaListAction;
use App\Domains\Lists\Actions\UpdateDuaListAction;
use App\Domains\Lists\Services\DuaListQueryService;
use App\Http\Requests\Api\V1\Lists\IndexListRequest;
use App\Http\Requests\Lists\StoreListRequest;
use App\Http\Requests\Lists\UpdateListRequest;
use App\Http\Resources\Api\V1\Lists\DuaListDetailResource;
use App\Http\Resources\Api\V1\Lists\DuaListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ListController extends ApiController
{
    public function index(IndexListRequest $request, DuaListQueryService $lists): JsonResponse
    {
        $paginator = $lists->paginateForUser(
            $request->user(),
            $request->listStatus(),
            $request->perPage(),
        );

        return $this->paginated($paginator, DuaListResource::class, 'Lists retrieved.');
    }

    public function store(StoreListRequest $request, CreateDuaListAction $action, DuaListQueryService $lists): JsonResponse
    {
        $duaList = $action($request->user(), $request->validated());
        $list = $lists->findOwnedForUser($request->user(), $duaList->id);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List created successfully.',
            201,
        );
    }

    public function show(int $duaList, DuaListQueryService $lists): JsonResponse
    {
        $list = $lists->findOwnedForUser(request()->user(), $duaList);

        Gate::authorize('view', $list);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List retrieved.',
        );
    }

    public function update(
        UpdateListRequest $request,
        int $duaList,
        UpdateDuaListAction $action,
        DuaListQueryService $lists,
    ): JsonResponse {
        $list = $lists->findOwnedForUser($request->user(), $duaList);

        Gate::authorize('update', $list);

        $action($list, $request->validated());
        $list = $lists->findOwnedForUser($request->user(), $list->id);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List updated successfully.',
        );
    }

    public function archive(int $duaList, ArchiveDuaListAction $action, DuaListQueryService $lists): JsonResponse
    {
        $list = $lists->findOwnedForUser(request()->user(), $duaList);

        Gate::authorize('archive', $list);

        $action($list);
        $list = $lists->findOwnedForUser(request()->user(), $list->id);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List archived successfully.',
        );
    }

    public function restore(int $duaList, RestoreDuaListAction $action, DuaListQueryService $lists): JsonResponse
    {
        $list = $lists->findOwnedForUser(request()->user(), $duaList);

        Gate::authorize('restore', $list);

        $action($list);
        $list = $lists->findOwnedForUser(request()->user(), $list->id);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List restored successfully.',
        );
    }

    public function destroy(int $duaList, DeleteDuaListAction $action, DuaListQueryService $lists): JsonResponse
    {
        $list = $lists->findOwnedForUser(request()->user(), $duaList);

        Gate::authorize('delete', $list);

        $action($list);

        return $this->success(null, 'List deleted successfully.');
    }
}
