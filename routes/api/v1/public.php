<?php

use App\Http\Controllers\Api\V1\Public\PublicListController;
use App\Http\Controllers\Api\V1\Public\PublicSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/public/lists/{slug}', [PublicListController::class, 'show'])->name('public.lists.show');

Route::post('/public/lists/{slug}/submissions', [PublicSubmissionController::class, 'store'])
    ->middleware('throttle:public-submissions')
    ->name('public.lists.submissions.store');
