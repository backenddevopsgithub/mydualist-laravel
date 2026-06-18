<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Profile\Actions\ChangeUserPasswordAction;
use App\Domains\Profile\Actions\UpdateListSettingsAction;
use App\Domains\Profile\Actions\UpdateUserProfileAction;
use App\Domains\Profile\Actions\UploadListImageAction;
use App\Exceptions\AdminExportDuplicateException;
use App\Exceptions\AdminExportQueueException;
use App\Exceptions\AdminExportRateLimitException;
use App\Http\Requests\Profile\DownloadSubmissionsRequest;
use App\Http\Requests\Profile\UpdateListSettingsRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadListImageRequest;
use App\Http\Resources\Api\V1\Lists\DuaListDetailResource;
use App\Http\Resources\Api\V1\Profile\ProfileResource;
use App\Services\AdminExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(
            (new ProfileResource($request->user()))->resolve(),
            'Profile retrieved.',
        );
    }

    public function update(UpdateProfileRequest $request, UpdateUserProfileAction $action): JsonResponse
    {
        $user = ($action)($request->user(), $request->validated());

        return $this->success(
            (new ProfileResource($user))->resolve(),
            'Profile updated successfully.',
        );
    }

    public function updatePassword(UpdatePasswordRequest $request, ChangeUserPasswordAction $action): JsonResponse
    {
        ($action)($request->user(), $request->validated());

        return $this->success(null, 'Password changed successfully.');
    }

    public function updateListSettings(
        UpdateListSettingsRequest $request,
        UpdateListSettingsAction $action,
        DuaListQueryService $lists,
    ): JsonResponse {
        ($action)($request->user(), $request->validated());
        $list = $lists->findOwnedForUser($request->user(), (int) $request->validated('dua_list_id'));

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List settings updated successfully.',
        );
    }

    public function uploadListImage(
        UploadListImageRequest $request,
        UploadListImageAction $action,
        DuaListQueryService $lists,
    ): JsonResponse {
        ($action)($request->user(), $request->validated(), $request->file('cover_image'));
        $list = $lists->findOwnedForUser($request->user(), (int) $request->validated('dua_list_id'));

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List image updated successfully.',
        );
    }

    public function exportSubmissions(
        DownloadSubmissionsRequest $request,
        AdminExportService $exports,
    ): JsonResponse {
        try {
            $export = $exports->queueUserListSubmissions(
                $request->user(),
                (int) $request->validated('dua_list_id'),
            );
        } catch (AdminExportDuplicateException|AdminExportRateLimitException|AdminExportQueueException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'export_id' => $export->id,
            'status' => $export->status->value,
        ], 'Export queued. You will receive an email when your CSV is ready to download.', 202);
    }
}
