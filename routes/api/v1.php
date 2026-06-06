<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1',
        'service' => config('mydualist.name'),
    ]);
})->name('api.v1.health');
