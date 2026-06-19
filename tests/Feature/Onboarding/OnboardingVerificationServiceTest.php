<?php

use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Domains\Onboarding\Services\OnboardingVerificationService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config([
        'mydualist.onboarding.test_otp' => 'random',
        'app.env' => 'testing',
    ]);
});

test('consecutive onboarding verification requests generate different otps', function () {
    $service = app(OnboardingVerificationService::class);
    $user = User::factory()->unverified()->create();

    $firstCode = $service->send($user);
    $secondCode = $service->send($user);

    expect($firstCode)->toMatch('/^\d{4}$/')
        ->and($secondCode)->toMatch('/^\d{4}$/')
        ->and($secondCode)->not->toBe($firstCode)
        ->and(Cache::get('onboarding-verify:'.$user->id))->toBe($secondCode);
});

test('onboarding verification rejects 0000 unless it was generated', function () {
    $service = app(OnboardingVerificationService::class);
    $user = User::factory()->unverified()->create();

    Cache::put('onboarding-verify:'.$user->id, '1234', now()->addMinutes(15));

    expect(fn () => $service->verify($user, '0000'))
        ->toThrow(ValidationException::class);
});

test('onboarding verification accepts 0000 when it was generated in non production test mode', function () {
    config(['mydualist.onboarding.test_otp' => '0000']);

    $service = app(OnboardingVerificationService::class);
    $user = User::factory()->unverified()->create();

    $code = $service->send($user);

    expect($code)->toBe('0000');

    $verifiedUser = $service->verify($user, '0000');

    expect($verifiedUser->hasVerifiedEmail())->toBeTrue();
});

test('onboarding verification test otp bypass is disabled in production', function () {
    config([
        'app.env' => 'production',
        'mydualist.onboarding.test_otp' => '0000',
    ]);

    $service = app(OnboardingVerificationService::class);
    $user = User::factory()->unverified()->create();

    $code = $service->send($user);

    expect($code)->not->toBe('0000')
        ->and($code)->toMatch('/^[1-9]\d{3}$/');
});

test('onboarding verification email uses the generated code', function () {
    Notification::fake();

    $service = app(OnboardingVerificationService::class);
    $user = User::factory()->unverified()->create();

    $code = $service->send($user);

    Notification::assertSentTo(
        $user,
        OnboardingVerificationCodeNotification::class,
        fn (OnboardingVerificationCodeNotification $notification) => $notification->toMail($user)->introLines[1] === $code,
    );
});
