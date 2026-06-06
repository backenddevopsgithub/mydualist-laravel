<?php

namespace App\Providers;

use App\Domains\Auth\Policies\UserPolicy;
use App\Models\User;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

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
}
