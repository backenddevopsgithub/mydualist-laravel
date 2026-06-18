<?php

use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommunityDuaController;
use App\Http\Controllers\Dashboard\CommunityDuaController as DashboardCommunityDuaController;
use App\Http\Controllers\Dashboard\DuaListController;
use App\Http\Controllers\Dashboard\ListSubmissionController;
use App\Http\Controllers\Dashboard\MySubmissionsController;
use App\Http\Controllers\Dashboard\PurchaseHistoryController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SupportController;
use App\Http\Controllers\Dashboard\UpgradeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundraisingRedirectController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Onboarding\CreateListOnboardingController;
use App\Http\Controllers\PurchaseCheckoutController;
use App\Http\Controllers\PublicDuaSubmissionController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DuaList;
use App\Support\Seo\SeoPresenter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'blogCategories' => BlogCategory::query()->ordered()->get(),
        'homepagePosts' => BlogPost::query()->published()->with('category')->latest('published_at')->take(12)->get(),
        'seo' => SeoPresenter::forRoute(
            'home',
            'The easiest way to collect dua requests',
            'The easiest way to collect dua requests for Hajj, Umrah, and every occasion.',
        ),
    ]);
})->name('home');

Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])
    ->middleware('throttle:newsletter')
    ->name('newsletter.subscribe');

Route::get('/submit-a-dua', [CommunityDuaController::class, 'create'])->name('community-dua.create');
Route::post('/submit-a-dua', [CommunityDuaController::class, 'storeFree'])
    ->middleware('throttle:public-submissions')
    ->name('community-dua.store');
Route::post('/submit-a-dua/checkout', [CommunityDuaController::class, 'checkout'])
    ->middleware('throttle:billing')
    ->name('community-dua.checkout');
Route::get('/submit-a-dua/success', [CommunityDuaController::class, 'success'])->name('community-dua.success');

Route::get('/billing/purchases/{purchase}/checkout', [PurchaseCheckoutController::class, 'show'])
    ->middleware('throttle:billing-purchase-read')
    ->name('billing.purchases.checkout');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-actions')->name('password.email');

    Route::get('/reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:password-actions')->name('password.update');
});

Route::get('/create-list', [CreateListOnboardingController::class, 'start'])->name('onboarding.start');
Route::get('/creator-mode', [CreateListOnboardingController::class, 'startCreator'])->name('onboarding.creator.start');
Route::get('/create-list/{step}', [CreateListOnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/create-list/verify', [CreateListOnboardingController::class, 'store'])
    ->middleware('throttle:otp')
    ->defaults('step', 'verify');
Route::post('/create-list/resend-code', [CreateListOnboardingController::class, 'resend'])
    ->middleware('throttle:otp')
    ->name('onboarding.resend');
Route::post('/create-list/{step}', [CreateListOnboardingController::class, 'store'])->middleware('throttle:onboarding')->name('onboarding.store');

Route::middleware('auth')->group(function (): void {
    Route::impersonate();
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/archived', DashboardController::class)->name('dashboard.archived');
    Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('dashboard.profile');
    Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::patch('/dashboard/profile/password', [ProfileController::class, 'password'])
        ->middleware('impersonate.protect')
        ->name('dashboard.profile.password');
    Route::patch('/dashboard/profile/list-settings', [ProfileController::class, 'listSettings'])->name('dashboard.profile.list-settings');
    Route::patch('/dashboard/profile/creator-mode', [ProfileController::class, 'creatorModeSettings'])->name('dashboard.profile.creator-mode');
    Route::post('/dashboard/profile/list-image', [ProfileController::class, 'listImage'])->name('dashboard.profile.list-image');
    Route::post('/dashboard/profile/submissions/export', [ProfileController::class, 'queueSubmissionsExport'])->name('dashboard.profile.submissions.export');
    Route::get('/dashboard/exports/{export}/download', \App\Http\Controllers\Dashboard\UserExportDownloadController::class)
        ->middleware('signed')
        ->name('dashboard.exports.download');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');
    Route::get('/dashboard/upgrade', UpgradeController::class)->name('dashboard.upgrade');
    Route::get('/dashboard/purchases', PurchaseHistoryController::class)->name('dashboard.purchases');
    Route::post('/billing/purchases/start', [PurchaseCheckoutController::class, 'store'])
        ->middleware(['throttle:billing', 'impersonate.protect'])
        ->name('billing.purchases.start');
    /** @deprecated Legacy Stripe Checkout Session — use billing.purchases.start */
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])
        ->middleware(['throttle:billing', 'impersonate.protect'])
        ->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->middleware('throttle:billing')->name('billing.success');
    Route::get('/dashboard/my-submissions', MySubmissionsController::class)->name('dashboard.submissions');
    Route::get('/dashboard/help', [SupportController::class, 'create'])->name('dashboard.support');
    Route::post('/dashboard/help', [SupportController::class, 'store'])->middleware('throttle:support')->name('dashboard.support.store');
    Route::get('/dashboard/lists/{duaList}', [ListSubmissionController::class, 'index'])->name('dashboard.lists.show');
    Route::get('/dashboard/lists/{duaList}/edit', [DuaListController::class, 'edit'])->name('dashboard.lists.edit');
    Route::patch('/dashboard/lists/{duaList}', [DuaListController::class, 'update'])->name('dashboard.lists.update');
    Route::patch('/dashboard/lists/{duaList}/archive', [DuaListController::class, 'archive'])->name('dashboard.lists.archive');
    Route::patch('/dashboard/lists/{duaList}/restore', [DuaListController::class, 'restore'])->name('dashboard.lists.restore');
    Route::delete('/dashboard/lists/{duaList}', [DuaListController::class, 'destroy'])->name('dashboard.lists.destroy');
    Route::post('/dashboard/lists/{duaList}/personal-duas', [ListSubmissionController::class, 'storePersonalDua'])->name('dashboard.lists.personal-duas.store');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/complete', [ListSubmissionController::class, 'complete'])->name('dashboard.submissions.complete');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/undo', [ListSubmissionController::class, 'undo'])->name('dashboard.submissions.undo');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/hide', [ListSubmissionController::class, 'hide'])->name('dashboard.submissions.hide');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/unhide', [ListSubmissionController::class, 'unhide'])->name('dashboard.submissions.unhide');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/archive', [ListSubmissionController::class, 'archive'])->name('dashboard.submissions.archive');
    Route::patch('/dashboard/lists/{duaList}/submissions/{submission}/report', [ListSubmissionController::class, 'report'])->name('dashboard.submissions.report');
    Route::patch('/dashboard/lists/{duaList}/community-duas/{communityDua}/complete', [DashboardCommunityDuaController::class, 'complete'])->name('dashboard.community-duas.complete');
    Route::patch('/dashboard/lists/{duaList}/community-duas/{communityDua}/skip', [DashboardCommunityDuaController::class, 'skip'])->name('dashboard.community-duas.skip');
    Route::patch('/dashboard/lists/{duaList}/community-duas/{communityDua}/report', [DashboardCommunityDuaController::class, 'report'])->name('dashboard.community-duas.report');
});

Route::post('/stripe/webhook', [BillingController::class, 'webhook'])->name('stripe.webhook');

Route::get('/lists/{duaList}', function (DuaList $duaList) {
    return redirect()->route('cms.show', $duaList);
})->name('dua-lists.show');

Route::post('/{duaList}/submissions', [PublicDuaSubmissionController::class, 'store'])
    ->middleware('throttle:public-submissions')
    ->name('dua-lists.submissions.store');

Route::get('/fundraising/redirect', [FundraisingRedirectController::class, 'redirect'])->name('fundraising.redirect');
Route::post('/{duaList}/fundraising/views', [FundraisingRedirectController::class, 'trackView'])
    ->middleware('throttle:public-submissions')
    ->name('fundraising.track-view');

Route::get('/{slug}', [CmsPageController::class, 'resolve'])
    ->name('cms.show');
