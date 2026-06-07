<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1',
        'service' => config('mydualist.name'),
    ]);
})->name('health');

require __DIR__.'/v1/auth.php';
require __DIR__.'/v1/public.php';
require __DIR__.'/v1/lists.php';
require __DIR__.'/v1/submissions.php';
require __DIR__.'/v1/billing.php';
require __DIR__.'/v1/profile.php';
require __DIR__.'/v1/support.php';
require __DIR__.'/v1/onboarding.php';

// Example admin-only route used for role restriction tests and future admin APIs.
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/ping', function () {
    return response()->json([
        'message' => 'Admin access granted.',
        'data' => ['scope' => 'admin'],
    ]);
})->name('admin.ping');
