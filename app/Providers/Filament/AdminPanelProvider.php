<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Http\Controllers\Admin\AdminExportDownloadController;
use App\Http\Controllers\Admin\AdminMediaServeController;
use App\Filament\Widgets\CategoryTrendsChart;
use App\Support\Impersonation;
use App\Filament\Widgets\EmailHealthWidget;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RecentSubmissionsTable;
use App\Filament\Widgets\SubmissionGrowthChart;
use App\Filament\Widgets\SystemHealthWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Support\Facades\Route;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandLogo(config('mydualist.brand.logo_url'))
            ->brandLogoHeight('2.75rem')
            ->authGuard('web')
            ->brandName(config('mydualist.name'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                SystemHealthWidget::class,
                EmailHealthWidget::class,
                PlatformStatsOverview::class,
                SubmissionGrowthChart::class,
                CategoryTrendsChart::class,
                RecentSubmissionsTable::class,
            ])
            ->middleware([
                'web',
                AuthenticateSession::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'stop-impersonating' => MenuItem::make()
                    ->label('Stop impersonating')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn (): string => route('impersonate.leave'))
                    ->visible(fn (): bool => Impersonation::isActive()),
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('/exports/{export}/download', AdminExportDownloadController::class)
                    ->middleware('signed')
                    ->name('exports.download');

                Route::get('/media/{media}/preview/{conversion?}', AdminMediaServeController::class)
                    ->middleware('signed')
                    ->name('media.preview');
            });
    }
}
