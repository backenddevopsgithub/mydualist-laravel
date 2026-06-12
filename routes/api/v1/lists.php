<?php

use App\Http\Controllers\Api\V1\ListController;
use App\Http\Controllers\Api\V1\ListSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::get('/lists', [ListController::class, 'index'])->name('lists.index');
    Route::post('/lists', [ListController::class, 'store'])->name('lists.store');
    Route::get('/lists/{duaList:id}', [ListController::class, 'show'])->name('lists.show');
    Route::patch('/lists/{duaList:id}', [ListController::class, 'update'])->name('lists.update');
    Route::patch('/lists/{duaList:id}/archive', [ListController::class, 'archive'])->name('lists.archive');
    Route::patch('/lists/{duaList:id}/restore', [ListController::class, 'restore'])->name('lists.restore');
    Route::delete('/lists/{duaList:id}', [ListController::class, 'destroy'])->name('lists.destroy');

    Route::get('/lists/{duaList:id}/submissions', [ListSubmissionController::class, 'index'])->name('lists.submissions.index');
    Route::post('/lists/{duaList:id}/personal-duas', [ListSubmissionController::class, 'storePersonalDua'])->name('lists.personal-duas.store');
    Route::patch('/lists/{duaList:id}/submissions/{submission}/complete', [ListSubmissionController::class, 'complete'])->name('lists.submissions.complete');
    Route::patch('/lists/{duaList:id}/submissions/{submission}/undo', [ListSubmissionController::class, 'undo'])->name('lists.submissions.undo');
    Route::patch('/lists/{duaList:id}/submissions/{submission}/hide', [ListSubmissionController::class, 'hide'])->name('lists.submissions.hide');
    Route::patch('/lists/{duaList:id}/submissions/{submission}/unhide', [ListSubmissionController::class, 'unhide'])->name('lists.submissions.unhide');
    Route::patch('/lists/{duaList:id}/submissions/{submission}/archive', [ListSubmissionController::class, 'archive'])->name('lists.submissions.archive');
    Route::post('/lists/{duaList:id}/submissions/{submission}/report', [ListSubmissionController::class, 'report'])->name('lists.submissions.report');
});
