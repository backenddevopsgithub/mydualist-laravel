<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboard\DuaListController;
use App\Http\Controllers\Dashboard\MySubmissionsController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\UpgradeController;
use App\Http\Controllers\Onboarding\CreateListOnboardingController;
use App\Http\Controllers\PublicDuaListController;
use App\Models\DuaList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::get('/create-list', [CreateListOnboardingController::class, 'start'])->name('onboarding.start');
Route::get('/create-list/{step}', [CreateListOnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/create-list/{step}', [CreateListOnboardingController::class, 'store'])->name('onboarding.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/archived', DashboardController::class)->name('dashboard.archived');
    Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('dashboard.profile');
    Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::patch('/dashboard/profile/password', [ProfileController::class, 'password'])->name('dashboard.profile.password');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');
    Route::get('/dashboard/upgrade', UpgradeController::class)->name('dashboard.upgrade');
    Route::get('/dashboard/my-submissions', MySubmissionsController::class)->name('dashboard.submissions');
    Route::get('/dashboard/lists/{duaList}/edit', [DuaListController::class, 'edit'])->name('dashboard.lists.edit');
    Route::patch('/dashboard/lists/{duaList}', [DuaListController::class, 'update'])->name('dashboard.lists.update');
    Route::patch('/dashboard/lists/{duaList}/archive', [DuaListController::class, 'archive'])->name('dashboard.lists.archive');
    Route::patch('/dashboard/lists/{duaList}/restore', [DuaListController::class, 'restore'])->name('dashboard.lists.restore');
    Route::delete('/dashboard/lists/{duaList}', [DuaListController::class, 'destroy'])->name('dashboard.lists.destroy');
});

Route::get('/lists/{duaList}', function (DuaList $duaList) {
    return redirect()->route('dua-lists.public', $duaList);
})->name('dua-lists.show');

Route::get('/{duaList}', [PublicDuaListController::class, 'show'])
    ->name('dua-lists.public');
