<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Auth\Actions\VerifyEmailAction;
use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OnboardingVerificationService extends Service
{
    private const CACHE_TTL_MINUTES = 15;

    public function __construct(
        private readonly VerifyEmailAction $verifyEmailAction,
    ) {}

    public function sendIfNeeded(User $user, bool $force = false): ?string
    {
        if ($user->hasVerifiedEmail()) {
            return null;
        }

        $cacheKey = $this->cacheKey($user);

        if (! $force && Cache::has($cacheKey)) {
            return null;
        }

        return $this->send($user);
    }

    public function send(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email address is already verified.',
            ]);
        }

        $code = $this->generateCode();

        Cache::put($this->cacheKey($user), $code, now()->addMinutes(self::CACHE_TTL_MINUTES));

        $user->notify(new OnboardingVerificationCodeNotification($code));

        return $code;
    }

    public function resend(User $user): string
    {
        return $this->send($user);
    }

    public function verify(User $user, string $code): User
    {
        $expected = Cache::get($this->cacheKey($user));

        if ($expected === null || ! hash_equals((string) $expected, $code)) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        Cache::forget($this->cacheKey($user));

        if ($user->hasVerifiedEmail()) {
            return $user;
        }

        return $this->verifyEmailAction->handle($user);
    }

    private function generateCode(): string
    {
        $testOtp = config('mydualist.onboarding.test_otp');

        if (
            config('app.env') !== 'production'
            && is_string($testOtp)
            && $testOtp !== ''
            && $testOtp !== 'random'
        ) {
            return str_pad($testOtp, 4, '0', STR_PAD_LEFT);
        }

        return (string) random_int(1000, 9999);
    }

    private function cacheKey(User $user): string
    {
        return 'onboarding-verify:'.$user->id;
    }
}
