<?php

namespace App\Providers;

use App\Domains\Cms\Services\CmsPageQueryService;
use App\Domains\Auth\Policies\UserPolicy;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Policies\DuaListPolicy;
use App\Policies\DuaSubmissionPolicy;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(DuaList::class, DuaListPolicy::class);
        Gate::policy(DuaSubmission::class, DuaSubmissionPolicy::class);
        Gate::define('start-billing-checkout', fn (User $user): bool => $user->isActive() && $user->hasVerifiedEmail());

        $this->configureRateLimiting();
        $this->configureViewComposers();

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
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

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

        RateLimiter::for('public-submissions', fn (Request $request) => [
            Limit::perMinute(8)->by($request->ip()),
            Limit::perHour(60)->by($request->ip()),
        ]);

        RateLimiter::for('billing', fn (Request $request) => [
            Limit::perMinute(6)->by((string) optional($request->user())->id ?: $request->ip()),
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
