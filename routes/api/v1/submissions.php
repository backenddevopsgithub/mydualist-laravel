<?php

use App\Http\Controllers\Api\V1\MySubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::get('/my-submissions', [MySubmissionController::class, 'index'])->name('my-submissions.index');
});
