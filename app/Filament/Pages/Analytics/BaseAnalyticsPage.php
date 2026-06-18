<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Concerns\HasAnalyticsExport;
use App\Models\User;
use App\Policies\AnalyticsPolicy;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

abstract class BaseAnalyticsPage extends Page implements HasTable
{
    use HasAnalyticsExport;
    use InteractsWithTable;

    public bool $metricsLoaded = false;

    protected static ?string $navigationGroup = 'Analytics';

    protected static string $view = 'filament.pages.analytics-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    public function loadMetrics(): void
    {
        $this->metricsLoaded = true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAnalyticsFilters(): array
    {
        $data = $this->tableFilters ?? [];
        $normalized = $data;

        if (isset($data['date_range']) && is_array($data['date_range'])) {
            $normalized = array_merge($normalized, $data['date_range']);
        }

        if (isset($data['creator_email']) && is_array($data['creator_email'])) {
            $normalized = array_merge($normalized, $data['creator_email']);
        }

        if (isset($data['include_admin_submissions']) && is_array($data['include_admin_submissions'])) {
            $normalized['include_admin_submissions'] = (bool) ($data['include_admin_submissions']['include_admin_submissions'] ?? false);
        }

        $occasion = $normalized['occasion']['value'] ?? $normalized['occasion'] ?? null;
        if ($occasion) {
            $normalized['category'] = $occasion;
        }

        $gender = $normalized['gender']['value'] ?? $normalized['gender'] ?? null;
        if ($gender) {
            $normalized['gender'] = $gender;
        }

        $duaListId = $normalized['dua_list_id']['value'] ?? $normalized['dua_list_id'] ?? null;
        if ($duaListId) {
            $normalized['dua_list_id'] = $duaListId;
        }

        $verified = $normalized['verified']['value'] ?? $normalized['verified'] ?? null;
        if ($verified) {
            $normalized['verified'] = $verified;
        }

        return array_filter(
            $normalized,
            fn (mixed $value, string $key): bool => $key === 'include_admin_submissions'
                ? (bool) $value
                : ($value !== null && $value !== ''),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return list<array{label: string, value: string|int|float, description?: string|null, color?: string|null, url?: string|null}>
     */
    abstract public function getMetricCards(): array;
}
