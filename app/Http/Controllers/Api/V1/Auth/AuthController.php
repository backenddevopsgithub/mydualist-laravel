<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Actions\RegisterUserAction;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $authToken = $action->handle($request->validated());

        return $this->success(
            (new AuthTokenResource($authToken))->resolve(),
            'Registration successful.',
            201,
        );
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $authToken = $action->handle($request->validated());

        return $this->success(
            (new AuthTokenResource($authToken))->resolve(),
            'Login successful.',
        );
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->handle($request->user(), $request->bearerToken());

        return $this->success(null, 'Logout successful.');
    }
}
