<?php

use App\Http\Controllers\Api\V1\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::post('/verification-code', [OnboardingController::class, 'sendVerificationCode'])
        ->middleware('throttle:otp')
        ->name('verification-code');

    Route::post('/verify-email', [OnboardingController::class, 'verifyEmail'])
        ->middleware('throttle:otp')
        ->name('verify-email');

    Route::post('/create-list', [OnboardingController::class, 'createList'])
        ->middleware('verified')
        ->name('create-list');
});
