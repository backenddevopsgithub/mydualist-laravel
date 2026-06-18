<?php

namespace App\Filament\Resources;

use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Resources\DuaSubmissionResource\Pages;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DuaSubmissionResource extends Resource
{
    protected static ?string $model = DuaSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('dua_list_id')
                ->label('List')
                ->options(fn () => DuaList::query()->latest()->limit(500)->pluck('title', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('first_name')->maxLength(60),
            TextInput::make('last_name')->maxLength(60),
            TextInput::make('email')->email()->maxLength(255),
            Select::make('status')
                ->options(self::statuses())
                ->required(),
            Textarea::make('content')->required()->rows(6),
            Textarea::make('note')->rows(3),
            Select::make('report_reason')->options([
                'spam' => 'Spam',
                'offensive' => 'Offensive content',
                'duplicate' => 'Duplicate',
                'irrelevant' => 'Irrelevant',
                'other' => 'Other',
            ]),
            Textarea::make('report_note')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('duaList.user')->latest())
            ->columns([
                TextColumn::make('display_name')
                    ->label('Name')
                    ->state(fn (DuaSubmission $record): string => $record->displayName())
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('duaList.title')->label('List')->searchable()->limit(32),
                TextColumn::make('content')->limit(60)->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('report_reason')->badge()->placeholder('None'),
                IconColumn::make('is_anonymous')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statuses()),
                SelectFilter::make('report_reason')->options([
                    'spam' => 'Spam',
                    'offensive' => 'Offensive content',
                    'duplicate' => 'Duplicate',
                    'irrelevant' => 'Irrelevant',
                    'other' => 'Other',
                ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('markCompleted')
                    ->label('Complete')
                    ->visible(fn (DuaSubmission $record): bool => $record->status !== DuaSubmissionStatus::Completed)
                    ->color('success')
                    ->action(fn (DuaSubmission $record) => app(TransitionDuaSubmissionStatusAction::class)($record, DuaSubmissionStatus::Completed)),
                Action::make('hide')
                    ->visible(fn (DuaSubmission $record): bool => $record->status !== DuaSubmissionStatus::Hidden)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (DuaSubmission $record) => app(TransitionDuaSubmissionStatusAction::class)($record, DuaSubmissionStatus::Hidden)),
                Action::make('restore')
                    ->visible(fn (DuaSubmission $record): bool => in_array($record->status, [DuaSubmissionStatus::Hidden, DuaSubmissionStatus::Archived, DuaSubmissionStatus::Reported], true))
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (DuaSubmission $record) => app(TransitionDuaSubmissionStatusAction::class)($record, DuaSubmissionStatus::Pending)),
            ])
            ->bulkActions([
                BulkAction::make('exportCsv')
                    ->label('Export CSV')
                    ->before(function (BulkAction $action, Collection $records): void {
                        $maxRows = (int) config('mydualist.admin_exports.max_bulk_selection_rows', 500);

                        if ($records->count() > $maxRows) {
                            Notification::make()
                                ->title('Export too large')
                                ->body("Select up to {$maxRows} submissions or use Submission Analytics export.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->action(fn (Collection $records): StreamedResponse => response()->streamDownload(function () use ($records): void {
                        $handle = fopen('php://output', 'w');
                        fputcsv($handle, ['ID', 'List', 'Name', 'Email', 'Status', 'Report Reason', 'Dua', 'Created At']);

                        foreach ($records->load('duaList') as $record) {
                            fputcsv($handle, [
                                $record->id,
                                $record->duaList?->title,
                                $record->displayName(),
                                $record->email,
                                $record->status->value,
                                $record->report_reason,
                                $record->content,
                                optional($record->created_at)->toDateTimeString(),
                            ]);
                        }

                        fclose($handle);
                    }, 'dua-submissions-export.csv')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDuaSubmissions::route('/'),
            'edit' => Pages\EditDuaSubmission::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statuses(): array
    {
        return [
            DuaSubmissionStatus::Pending->value => 'Incomplete Duas',
            DuaSubmissionStatus::Completed->value => 'Completed',
            DuaSubmissionStatus::Hidden->value => 'Hidden',
            DuaSubmissionStatus::Archived->value => 'Archived',
            DuaSubmissionStatus::Reported->value => 'Reported',
        ];
    }
}
