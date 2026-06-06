<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Auth\Actions\ResetPasswordAction;
use App\Domains\Auth\Actions\SendPasswordResetLinkAction;
use App\Domains\Auth\Actions\VerifyEmailAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends ApiController
{
    public function verify(Request $request, string $id, string $hash, VerifyEmailAction $action): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            throw new DomainException('Verification link is invalid or has expired.', 'verification_link_expired');
        }

        $user = User::query()->findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new DomainException('Verification link is invalid.', 'invalid_verification_link');
        }

        $user = $action->handle($user);

        return $this->success(
            (new UserResource($user))->resolve(),
            'Email verified successfully.',
        );
    }

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            throw new DomainException('Email address is already verified.', 'email_already_verified');
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->success(null, 'Verification link sent.');
    }
}
