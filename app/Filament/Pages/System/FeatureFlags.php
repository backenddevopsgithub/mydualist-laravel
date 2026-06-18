<?php

namespace App\Filament\Pages\System;

use App\Models\User;
use App\Policies\AnalyticsPolicy;
use App\Services\FeatureFlagService;
use Filament\Pages\Page;

class FeatureFlags extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.feature-flags';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    /**
     * @return list<array{key: string, label: string, enabled: bool, source: string}>
     */
    public function getFlags(): array
    {
        return app(FeatureFlagService::class)->flags();
    }
}
