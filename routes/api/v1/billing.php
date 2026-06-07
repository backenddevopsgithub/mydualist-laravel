<?php

use App\Http\Controllers\Api\V1\Billing\CheckoutController;
use App\Http\Controllers\Api\V1\Billing\EntitlementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::get('/billing/entitlements', EntitlementController::class)->name('billing.entitlements');
    Route::post('/billing/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:billing')
        ->name('billing.checkout');
    Route::get('/billing/checkout/{sessionId}', [CheckoutController::class, 'show'])
        ->middleware('throttle:billing')
        ->name('billing.checkout.status');
});
