<?php

namespace App\Providers;

use App\Domains\Billing\Services\EntitlementGrantService;
use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Cms\Services\CmsPageQueryService;
use App\Domains\Auth\Policies\UserPolicy;
use App\Models\AdminExport;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\MediaLibraryItem;
use App\Models\User;
use App\Observers\AnalyticsCacheInvalidationObserver;
use App\Observers\DuaSubmissionCounterObserver;
use App\Policies\AdminExportPolicy;
use App\Policies\AnalyticsPolicy;
use App\Policies\BillingPurchasePolicy;
use App\Policies\EntitlementGrantPolicy;
use App\Policies\DuaListPolicy;
use App\Policies\DuaSubmissionPolicy;
use App\Policies\MediaLibraryPolicy;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EntitlementGrantService::class);
        $this->app->singleton(EntitlementResolverService::class);
        $this->app->singleton(UserEntitlementService::class);
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AdminExport::class, AdminExportPolicy::class);
        Gate::define('view-analytics', [AnalyticsPolicy::class, 'viewAny']);
        Gate::policy(BillingPurchase::class, BillingPurchasePolicy::class);
        Gate::policy(EntitlementGrant::class, EntitlementGrantPolicy::class);
        Gate::policy(DuaList::class, DuaListPolicy::class);
        Gate::policy(DuaSubmission::class, DuaSubmissionPolicy::class);
        Gate::policy(MediaLibraryItem::class, MediaLibraryPolicy::class);
        Gate::define('start-billing-checkout', fn (User $user): bool => $user->isActive() && $user->hasVerifiedEmail());

        $this->configureRateLimiting();
        $this->configureViewComposers();
        $this->configureAnalyticsCacheInvalidation();
        $this->configureSubmissionCounters();
        $this->configureAuthEvents();

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.hooks.livewire-styles')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => view('filament.hooks.load-dark-mode-fallback')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => view('filament.hooks.livewire-scripts')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => view('filament.hooks.impersonation-banner')->render(),
        );
    }

    private function configureRateLimiting(): void
    {
        $loadTesting = (bool) config('mydualist.load_testing.enabled');

        RateLimiter::for('login', fn (Request $request) => $loadTesting
            ? [Limit::perMinute((int) config('mydualist.load_testing.login_per_minute', 1000))->by($request->ip())]
            : [Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip())]);

        RateLimiter::for('password-actions', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('onboarding', fn (Request $request) => [
            Limit::perMinute(10)->by((string) optional($request->user())->id ?: $request->ip()),
        ]);

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(5)->by((string) optional($request->user())->id ?: $request->ip()),
        ]);

        RateLimiter::for('public-submissions', fn (Request $request) => $loadTesting
            ? [
                Limit::perMinute((int) config('mydualist.load_testing.public_submissions_per_minute', 10000))->by($request->ip()),
                Limit::perHour((int) config('mydualist.load_testing.public_submissions_per_hour', 100000))->by($request->ip()),
            ]
            : [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perHour(60)->by($request->ip()),
            ]);

        RateLimiter::for('billing', fn (Request $request) => [
            Limit::perMinute(6)->by((string) optional($request->user())->id ?: $request->ip()),
        ]);

        RateLimiter::for('billing-purchase-read', fn (Request $request) => [
            Limit::perMinute(60)->by((string) optional($request->user())->id ?: $request->ip()),
        ]);

        RateLimiter::for('support', fn (Request $request) => [
            Limit::perMinute(3)->by((string) optional($request->user())->id ?: $request->ip()),
        ]);

        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
        ]);

        RateLimiter::for('newsletter', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
        ]);

        RateLimiter::for('admin-exports', fn (Request $request) => [
            Limit::perHour((int) config('mydualist.admin_exports.rate_limit_per_hour', 10))
                ->by((string) optional($request->user())->id ?: $request->ip()),
        ]);
    }

    private function configureAnalyticsCacheInvalidation(): void
    {
        $observer = AnalyticsCacheInvalidationObserver::class;

        User::observe($observer);
        DuaList::observe($observer);
        DuaSubmission::observe($observer);
    }

    private function configureSubmissionCounters(): void
    {
        DuaSubmission::observe(DuaSubmissionCounterObserver::class);
    }

    private function configureAuthEvents(): void
    {
        // OTP via OnboardingVerificationService replaces Laravel's default verification link email.
        Event::forget(Registered::class);
    }

    private function configureViewComposers(): void
    {
        View::composer(
            ['partials.marketing-footer', 'partials.cms-footer-links', 'partials.public-legal-footer'],
            function ($view): void {
                $view->with('cmsFooterPages', app(CmsPageQueryService::class)->publishedFooterPages());
            },
        );
    }
}
