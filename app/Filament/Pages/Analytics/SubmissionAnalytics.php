<?php

namespace App\Filament\Pages\Analytics;

use App\Enums\AdminExportType;
use App\Filament\Resources\DuaSubmissionResource;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\AnalyticsQueryService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionAnalytics extends BaseAnalyticsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Submission Analytics';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Submission Analytics';

    protected function analyticsExportType(): AdminExportType
    {
        return AdminExportType::SubmissionAnalytics;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('moderateSubmissions')
                ->label('Moderate submissions')
                ->icon('heroicon-o-shield-exclamation')
                ->url(DuaSubmissionResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getMetricCards(): array
    {
        if (! $this->metricsLoaded) {
            return [];
        }

        $metrics = app(AnalyticsQueryService::class)->submissionMetrics($this->getAnalyticsFilters());

        return [
            ['label' => 'Total Submissions', 'value' => number_format($metrics['total_submissions'])],
            ['label' => 'Admin Test Submissions', 'value' => number_format($metrics['admin_test_submissions'])],
            ['label' => 'Unique Submitters', 'value' => number_format($metrics['unique_submitters'])],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(AnalyticsQueryService::class)->submissionAnalyticsQuery([]))
            ->columns([
                TextColumn::make('content')
                    ->label('Submission Title')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('email')->label('User Email')->searchable(),
                TextColumn::make('gender')->label('Gender')->badge(),
                TextColumn::make('duaList.title')->label('Dua List Name')->searchable(),
                TextColumn::make('phone')
                    ->label('Phone Number')
                    ->state(fn (DuaSubmission $record): string => trim(implode(' ', array_filter([
                        $record->whatsapp_country_code,
                        $record->whatsapp_phone,
                    ]))))
                    ->searchable(['whatsapp_phone']),
                TextColumn::make('created_at')->label('Submitted Date')->dateTime()->sortable(),
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
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),
                SelectFilter::make('dua_list_id')
                    ->label('Dua List')
                    ->options(fn (): array => DuaList::query()->orderBy('title')->limit(500)->pluck('title', 'id')->all())
                    ->searchable(),
                Filter::make('include_admin_submissions')
                    ->form([
                        Toggle::make('include_admin_submissions')->label('Include Admin Submissions')->default(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => ($data['include_admin_submissions'] ?? false)
                        ? $query
                        : $query->whereNotAdminTest()),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No submissions found')
            ->emptyStateDescription('Try adjusting your filters or date range.')
            ->headerActions([
                $this->exportTableAction(),
            ]);
    }
}
