<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Models\DuaList;
use App\Services\AnalyticsQueryService;
use App\Support\DuaListOccasions;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Category Analytics';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Category Analytics';

    public bool $chartsLoaded = false;

    protected ?Collection $cachedCategoryTableRows = null;

    protected static string $view = 'filament.pages.category-analytics';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::CategoryAnalytics;
    }

    public function loadMetrics(): void
    {
        parent::loadMetrics();
        $this->chartsLoaded = true;
    }

    public function getMetricCards(): array
    {
        if (! $this->metricsLoaded) {
            return [];
        }

        $metrics = app(AnalyticsQueryService::class)->categoryMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Categories', 'value' => number_format($metrics['total_categories'])],
            ['label' => 'Total Lists', 'value' => number_format($metrics['total_lists'])],
            [
                'label' => 'Top Category',
                'value' => $metrics['top_categories'][0]['label'] ?? '—',
                'description' => isset($metrics['top_categories'][0])
                    ? number_format($metrics['top_categories'][0]['list_count']).' lists'
                    : null,
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function getDonutChartData(): array
    {
        $metrics = app(AnalyticsQueryService::class)->categoryMetrics($this->getAnalyticsFilters());
        $top = collect($metrics['top_categories'])->take(8);

        return [
            'labels' => $top->pluck('label')->all(),
            'data' => $top->pluck('list_count')->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function getTrendChartData(): array
    {
        return app(AnalyticsQueryService::class)->categoryTrendData($this->getAnalyticsFilters());
    }

    /**
     * @return Collection<int, object{occasion: string, label: string, list_count: int, percentage: float}>
     */
    public function categoryTableRows(): Collection
    {
        return $this->cachedCategoryTableRows ??= app(AnalyticsQueryService::class)
            ->categoryAnalyticsRows($this->getAnalyticsFilters());
    }

    public function updated($property): void
    {
        if (is_string($property) && str_starts_with($property, 'tableFilters')) {
            $this->cachedCategoryTableRows = null;
        }
    }

    public function getTableRecordKey($record): string
    {
        if (isset($record->occasion) && $record->occasion !== null && $record->occasion !== '') {
            return (string) $record->occasion;
        }

        return md5(json_encode($record->getAttributes()));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return DuaList::query()
                    ->select('occasion')
                    ->selectRaw('COUNT(*) as list_count')
                    ->groupBy('occasion')
                    ->reorder()
                    ->orderByDesc('list_count');
            })
            ->columns([
                TextColumn::make('occasion')
                    ->label('Category Name')
                    ->formatStateUsing(fn (string $state): string => DuaListOccasions::label($state))
                    ->searchable(),
                TextColumn::make('list_count')->label('Total Lists')->sortable(),
                TextColumn::make('percentage')
                    ->label('Percentage of Total')
                    ->state(function (DuaList $record, CategoryAnalytics $livewire): string {
                        $row = $livewire->categoryTableRows()->firstWhere('occasion', $record->occasion);
                        $percentage = $row->percentage ?? 0.0;

                        return $percentage.'%';
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('Date From'),
                        DatePicker::make('date_to')->label('Date To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['date_from'])) {
                            $query->where('created_at', '>=', $data['date_from']);
                        }

                        if (! empty($data['date_to'])) {
                            $query->where('created_at', '<=', $data['date_to'].' 23:59:59');
                        }

                        return $query;
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No categories found')
            ->emptyStateDescription('No dua lists match the selected date range.')
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }
}
