<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Models\User;
use App\Services\AnalyticsQueryService;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UniqueUsersAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Unique Users';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Unique Users';

    protected static ?string $slug = 'unique-users';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::UniqueUsers;
    }

    public function getMetricCards(): array
    {
        if (! $this->metricsLoaded) {
            return [];
        }

        $metrics = app(AnalyticsQueryService::class)->uniqueUsersMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Registered Users', 'value' => number_format($metrics['total_registered'])],
            ['label' => 'Verified Users', 'value' => number_format($metrics['verified'])],
            ['label' => 'Unverified Users', 'value' => number_format($metrics['unverified'])],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query())
            ->columns([
                TextColumn::make('index')->label('S.No')->rowIndex(),
                TextColumn::make('name')->label('Username')->searchable(),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('email')->label('User Email')->searchable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                TextColumn::make('created_at')->label('Registration Date')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('verified')
                    ->options([
                        'verified' => 'Verified',
                        'unverified' => 'Unverified',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'verified' => $query->whereNotNull('email_verified_at'),
                        'unverified' => $query->whereNull('email_verified_at'),
                        default => $query,
                    }),
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
            ->emptyStateHeading('No registered users found')
            ->emptyStateDescription('Try adjusting your filters or date range.')
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }
}
