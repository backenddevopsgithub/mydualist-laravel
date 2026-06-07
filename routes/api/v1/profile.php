<?php

use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/list-settings', [ProfileController::class, 'updateListSettings'])->name('profile.list-settings');
    Route::post('/profile/list-image', [ProfileController::class, 'uploadListImage'])->name('profile.list-image');
    Route::get('/profile/submissions/export', [ProfileController::class, 'exportSubmissions'])->name('profile.submissions.export');
});
