<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Models\User;
use App\Services\AnalyticsQueryService;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Keyword Analytics';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Keyword Analytics';

    public bool $chartsLoaded = false;

    protected static string $view = 'filament.pages.keyword-analytics';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::KeywordAnalytics;
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

        $metrics = app(AnalyticsQueryService::class)->keywordMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Keywords', 'value' => number_format($metrics['total_keywords'])],
            ['label' => 'Unique Keywords', 'value' => number_format($metrics['unique_keywords'])],
            ['label' => 'Top Keyword', 'value' => $this->topKeywordLabel()],
        ];
    }

    private function topKeywordLabel(): string
    {
        $top = app(AnalyticsQueryService::class)->keywordOccurrences($this->getAnalyticsFilters(), 1)->first();

        return $top ? $top->keyword.' ('.number_format($top->occurrences).')' : '—';
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function getTopKeywordsChartData(): array
    {
        $rows = app(AnalyticsQueryService::class)->keywordOccurrences($this->getAnalyticsFilters(), 10);

        return [
            'labels' => $rows->pluck('keyword')->all(),
            'data' => $rows->pluck('occurrences')->all(),
        ];
    }

    /**
     * @return list<array{keyword: string, size: int}>
     */
    public function getWordCloudData(): array
    {
        return app(AnalyticsQueryService::class)
            ->keywordOccurrences($this->getAnalyticsFilters(), 50)
            ->map(fn (object $row): array => [
                'keyword' => $row->keyword,
                'size' => min(32, max(12, (int) round($row->occurrences / 2))),
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()->whereRaw('0 = 1'))
            ->columns([
                TextColumn::make('serial')
                    ->label('S.No')
                    ->state(fn (object $record, $livewire): int => (($livewire->getTablePage() - 1) * (int) $livewire->getTableRecordsPerPage()) + ($livewire->getTableRecords()->getCollection()->search($record) + 1)),
                TextColumn::make('keyword')
                    ->label('Keyword')
                    ->state(fn (object $record): string => $record->keyword ?? ''),
                TextColumn::make('occurrences')
                    ->label('Occurrences')
                    ->state(fn (object $record): int => (int) ($record->occurrences ?? 0)),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('Date From'),
                        DatePicker::make('date_to')->label('Date To'),
                    ]),
            ])
            ->emptyStateHeading('No keywords found')
            ->emptyStateDescription('Try adjusting your date range filters.')
            ->recordAction(null)
            ->recordUrl(null)
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }

    public function getTableRecords(): Paginator | CursorPaginator
    {
        $filters = $this->getAnalyticsFilters();
        $search = $this->getTableSearch();

        $rows = app(AnalyticsQueryService::class)->keywordOccurrences($filters);

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn (object $row): bool => str_contains($row->keyword, $needle));
        }

        $perPage = (int) ($this->getTableRecordsPerPage() ?: 25);
        $page = $this->getTablePage();

        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $this->getTablePaginationPageName()],
        );
    }
}
