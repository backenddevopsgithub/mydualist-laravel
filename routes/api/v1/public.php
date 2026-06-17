<?php

use App\Http\Controllers\Api\V1\Public\PublicListController;
use App\Http\Controllers\Api\V1\Public\PublicListSuggestionController;
use App\Http\Controllers\Api\V1\Public\PublicSubmissionController;
use App\Http\Controllers\Api\V1\Public\SubmissionOtpController;
use Illuminate\Support\Facades\Route;

Route::get('/public/lists/{slug}', [PublicListController::class, 'show'])->name('public.lists.show');

Route::get('/public/lists/{slug}/suggestions', [PublicListSuggestionController::class, 'index'])
    ->name('public.lists.suggestions.index');

Route::post('/public/lists/{slug}/submissions', [PublicSubmissionController::class, 'store'])
    ->middleware('throttle:public-submissions')
    ->name('public.lists.submissions.store');

Route::post('/public/submissions/otp/send', [SubmissionOtpController::class, 'send'])
    ->middleware('throttle:otp')
    ->name('public.submissions.otp.send');

Route::post('/public/submissions/otp/verify', [SubmissionOtpController::class, 'verify'])
    ->middleware('throttle:otp')
    ->name('public.submissions.otp.verify');
