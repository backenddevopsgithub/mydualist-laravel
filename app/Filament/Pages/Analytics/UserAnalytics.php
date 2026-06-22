<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Models\User;
use App\Services\AnalyticsQueryService;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'User Analytics';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'User Analytics';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::UserAnalytics;
    }

    public function getMetricCards(): array
    {
        if (! $this->metricsLoaded) {
            return [];
        }

        $metrics = app(AnalyticsQueryService::class)->userMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Users', 'value' => number_format($metrics['total_users'])],
            ['label' => 'Total Lists Created', 'value' => number_format($metrics['total_lists'])],
            ['label' => 'Average Lists Per User', 'value' => $metrics['avg_lists_per_user']],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(AnalyticsQueryService::class)->userAnalyticsQuery([]))
            ->columns([
                TextColumn::make('index')->label('S.No')->rowIndex(),
                TextColumn::make('name')->label('Username')->searchable(),
                TextColumn::make('email')->label('User Email')->searchable(),
                TextColumn::make('dua_lists_count')->label('Number of Dua Lists')->sortable(),
                TextColumn::make('dua_submissions_count')->label('Total Dua Submissions')->sortable(),
                TextColumn::make('created_at')->label('Registration Date')->dateTime()->sortable(),
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('Try adjusting your date range filters.')
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }
}
