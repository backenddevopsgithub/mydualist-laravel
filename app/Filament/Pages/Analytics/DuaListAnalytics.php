<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Models\DuaList;
use App\Services\AnalyticsQueryService;
use App\Support\DuaListOccasions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class DuaListAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Dua List Analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dua List Analytics';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::DuaListAnalytics;
    }

    public function getMetricCards(): array
    {
        if (! $this->metricsLoaded) {
            return [];
        }

        $metrics = app(AnalyticsQueryService::class)->duaListMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Lists', 'value' => number_format($metrics['total_lists'])],
            ['label' => 'Total Submissions', 'value' => number_format($metrics['total_submissions'])],
            ['label' => 'Completion Rate', 'value' => $metrics['completion_rate'].'%'],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DuaList::query()
                ->with(['user:id,name,email']))
            ->columns([
                TextColumn::make('index')
                    ->label('S.No')
                    ->rowIndex(),
                TextColumn::make('title')->label('List Name')->searchable(),
                TextColumn::make('user.name')->label('Creator Name')->searchable(),
                TextColumn::make('user.email')->label('Creator Email')->searchable(),
                TextColumn::make('occasion')
                    ->label('Category')
                    ->formatStateUsing(fn (string $state): string => DuaListOccasions::label($state)),
                TextColumn::make('submissions_count')->label('Total Submissions')->sortable(),
                TextColumn::make('completed_submissions_count')->label('Completed Submissions')->sortable(),
                TextColumn::make('created_at')->label('Created Date')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('creator_email')
                    ->form([
                        TextInput::make('creator_email')->label('Creator Email'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['creator_email'] ?? null)
                        ? $query->whereHas('user', fn (Builder $q) => $q->where('email', 'like', '%'.$data['creator_email'].'%'))
                        : $query),
                SelectFilter::make('occasion')
                    ->label('Category')
                    ->options(DuaListOccasions::labels()),
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No dua lists found')
            ->emptyStateDescription('Try adjusting your filters or date range.')
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }
}
