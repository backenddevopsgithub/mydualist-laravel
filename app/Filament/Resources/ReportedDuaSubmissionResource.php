<?php

namespace App\Filament\Resources;

use App\Domains\Submissions\Actions\DismissReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\HideReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\RestoreReportedDuaSubmissionAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Resources\ReportedDuaSubmissionResource\Pages;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Support\DuaListOccasions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportedDuaSubmissionResource extends Resource
{
    protected static ?string $model = DuaSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Reported Duas';

    protected static ?string $modelLabel = 'Reported Dua';

    protected static ?string $pluralModelLabel = 'Reported Duas';

    protected static ?string $slug = 'reported-duas';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->reported()
                ->with(['duaList', 'moderatedBy'])
                ->latest('reported_at'))
            ->columns([
                TextColumn::make('content')
                    ->label('Dua preview')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('duaList.title')
                    ->label('List title')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas(
                            'duaList',
                            fn (Builder $query): Builder => $query->where('title', 'like', "%{$search}%"),
                        );
                    })
                    ->limit(32),
                TextColumn::make('display_name')
                    ->label('Submitter')
                    ->state(fn (DuaSubmission $record): string => $record->displayName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Submitter email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('report_reason')
                    ->label('Report reason')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::reportReasonLabel($state)),
                TextColumn::make('report_count')
                    ->label('Report count')
                    ->sortable(),
                TextColumn::make('reported_at')
                    ->label('Date reported')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Current status')
                    ->badge()
                    ->formatStateUsing(fn (DuaSubmissionStatus $state): string => self::statusLabel($state)),
                TextColumn::make('moderatedBy.name')
                    ->label('Moderated by')
                    ->placeholder('—'),
                TextColumn::make('moderated_at')
                    ->label('Moderated at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('report_reason')
                    ->label('Report reason')
                    ->options(self::reportReasons()),
                Filter::make('reported_at')
                    ->label('Date reported')
                    ->form([
                        DatePicker::make('reported_from')
                            ->label('From'),
                        DatePicker::make('reported_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['reported_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('reported_at', '>=', $date),
                            )
                            ->when(
                                $data['reported_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('reported_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('occasion')
                    ->label('List occasion')
                    ->options(DuaListOccasions::labels())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $occasion): Builder => $query->whereHas(
                            'duaList',
                            fn (Builder $query): Builder => $query->where('occasion', $occasion),
                        ),
                    )),
                SelectFilter::make('status')
                    ->label('Submission status')
                    ->options(self::statuses()),
                TernaryFilter::make('moderated')
                    ->label('Moderated')
                    ->placeholder('All')
                    ->trueLabel('Moderated')
                    ->falseLabel('Unmoderated')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('moderated_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('moderated_at'),
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View full submission'),
                Action::make('hide')
                    ->label('Hide submission')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (DuaSubmission $record): bool => $record->status !== DuaSubmissionStatus::Hidden)
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('moderation_notes')
                            ->label('Moderation notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (DuaSubmission $record, array $data): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();

                        app(HideReportedDuaSubmissionAction::class)(
                            $record,
                            $moderator,
                            $data['moderation_notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Submission hidden')
                            ->success()
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Restore submission')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (DuaSubmission $record): bool => $record->status !== DuaSubmissionStatus::Pending)
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('moderation_notes')
                            ->label('Moderation notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (DuaSubmission $record, array $data): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();

                        app(RestoreReportedDuaSubmissionAction::class)(
                            $record,
                            $moderator,
                            $data['moderation_notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Submission restored to pending')
                            ->success()
                            ->send();
                    }),
                Action::make('dismiss')
                    ->label('Dismiss report')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('moderation_notes')
                            ->label('Moderation notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (DuaSubmission $record, array $data): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();

                        app(DismissReportedDuaSubmissionAction::class)(
                            $record,
                            $moderator,
                            $data['moderation_notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Report dismissed')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('hideSelected')
                    ->label('Hide selected')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->authorize('moderateAny', DuaSubmission::class)
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();
                        $action = app(HideReportedDuaSubmissionAction::class);

                        $records->each(function (DuaSubmission $record) use ($action, $moderator): void {
                            if ($record->status === DuaSubmissionStatus::Hidden) {
                                return;
                            }

                            $action($record, $moderator);
                        });

                        Notification::make()
                            ->title('Selected submissions hidden')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('restoreSelected')
                    ->label('Restore selected')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->authorize('moderateAny', DuaSubmission::class)
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();
                        $action = app(RestoreReportedDuaSubmissionAction::class);

                        $records->each(function (DuaSubmission $record) use ($action, $moderator): void {
                            if ($record->status === DuaSubmissionStatus::Pending) {
                                return;
                            }

                            $action($record, $moderator);
                        });

                        Notification::make()
                            ->title('Selected submissions restored')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('dismissSelected')
                    ->label('Dismiss selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->authorize('moderateAny', DuaSubmission::class)
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        /** @var User $moderator */
                        $moderator = auth()->user();
                        $action = app(DismissReportedDuaSubmissionAction::class);

                        $records->each(fn (DuaSubmission $record) => $action($record, $moderator));

                        Notification::make()
                            ->title('Selected reports dismissed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportedDuaSubmissions::route('/'),
            'view' => Pages\ViewReportedDuaSubmission::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('moderateAny', DuaSubmission::class);
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('moderate', $record) ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    private static function reportReasons(): array
    {
        return [
            'spam' => 'Spam',
            'offensive' => 'Offensive content',
            'duplicate' => 'Duplicate',
            'irrelevant' => 'Irrelevant',
            'other' => 'Other',
        ];
    }

    private static function reportReasonLabel(?string $reason): string
    {
        if ($reason === null) {
            return 'Unknown';
        }

        return self::reportReasons()[$reason] ?? str($reason)->headline()->toString();
    }

    /**
     * @return array<string, string>
     */
    private static function statuses(): array
    {
        return [
            DuaSubmissionStatus::Pending->value => 'Incomplete',
            DuaSubmissionStatus::Completed->value => 'Completed',
            DuaSubmissionStatus::Hidden->value => 'Hidden',
            DuaSubmissionStatus::Archived->value => 'Archived',
            DuaSubmissionStatus::Reported->value => 'Reported',
        ];
    }

    private static function statusLabel(DuaSubmissionStatus $status): string
    {
        return self::statuses()[$status->value] ?? $status->value;
    }
}
