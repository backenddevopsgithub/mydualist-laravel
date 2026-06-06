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

// Example admin-only route used for role restriction tests and future admin APIs.
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/ping', function () {
    return response()->json([
        'message' => 'Admin access granted.',
        'data' => ['scope' => 'admin'],
    ]);
})->name('admin.ping');
