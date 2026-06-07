<?php

use App\Http\Controllers\Api\V1\SupportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])
    ->post('/support', [SupportController::class, 'store'])
    ->middleware('throttle:support')
    ->name('support.store');
