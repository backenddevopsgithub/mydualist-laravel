<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Auth\Actions\ResetPasswordAction;
use App\Domains\Auth\Actions\SendPasswordResetLinkAction;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends ApiController
{
    public function forgot(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->handle($request->validated());

        return $this->success(
            null,
            __(Password::RESET_LINK_SENT),
        );
    }

    public function reset(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        return $this->success(
            (new UserResource($user))->resolve(),
            'Password reset successful.',
        );
    }
}
