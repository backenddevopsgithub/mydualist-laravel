<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Onboarding\Actions\CreateOnboardingListAction;
use App\Domains\Onboarding\Services\OnboardingVerificationService;
use App\Exceptions\DomainException;
use App\Http\Requests\Onboarding\StoreOnboardingListRequest;
use App\Http\Requests\Onboarding\VerifyOnboardingEmailRequest;
use App\Http\Resources\Api\V1\Lists\DuaListDetailResource;
use App\Http\Resources\Api\V1\Profile\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends ApiController
{
    public function sendVerificationCode(Request $request, OnboardingVerificationService $verification): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email address is already verified.', 'email_already_verified');
        }

        $verification->send($user);

        return $this->success(null, 'Verification code sent.');
    }

    public function verifyEmail(
        VerifyOnboardingEmailRequest $request,
        OnboardingVerificationService $verification,
    ): JsonResponse {
        $user = $verification->verify($request->user(), $request->validated('code'));

        return $this->success(
            (new ProfileResource($user))->resolve(),
            'Email verified successfully.',
        );
    }

    public function createList(
        StoreOnboardingListRequest $request,
        CreateOnboardingListAction $action,
        DuaListQueryService $lists,
    ): JsonResponse {
        $duaList = ($action)(
            $request->user(),
            $request->safe()->except('cover_image'),
            $request->file('cover_image'),
        );

        $list = $lists->findOwnedForUser($request->user(), $duaList->id);

        return $this->success(
            (new DuaListDetailResource($list))->resolve(),
            'List created successfully.',
            201,
        );
    }
}
