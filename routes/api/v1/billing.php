<?php

use App\Http\Controllers\Api\V1\Billing\CheckoutController;
use App\Http\Controllers\Api\V1\Billing\EntitlementController;
use App\Http\Controllers\Api\V1\Billing\PurchaseController;
use App\Http\Controllers\Api\V1\Billing\WebhookController;
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

Route::post('/billing/purchases', [PurchaseController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:billing'])
    ->name('billing.purchases.store');

Route::middleware(['auth:sanctum', 'throttle:billing-purchase-read'])->group(function (): void {
    Route::get('/billing/purchases/{purchase}', [PurchaseController::class, 'show'])
        ->name('billing.purchases.show');
    Route::get('/billing/purchases/{purchase}/client-secret', [PurchaseController::class, 'clientSecret'])
        ->name('billing.purchases.client-secret');
    Route::get('/billing/purchases/{purchase}/payment-status', [PurchaseController::class, 'paymentStatus'])
        ->name('billing.purchases.payment-status');
});

Route::post('/billing/webhooks/stripe', [WebhookController::class, 'store'])
    ->name('billing.webhooks.stripe');
