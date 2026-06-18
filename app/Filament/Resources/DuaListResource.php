<?php

namespace App\Filament\Resources;

use App\Domains\Lists\Actions\ArchiveDuaListAction;
use App\Domains\Lists\Actions\RestoreDuaListAction;
use App\Filament\Resources\DuaListResource\Pages;
use App\Models\DuaList;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DuaListResource extends Resource
{
    protected static ?string $model = DuaList::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')
                ->label('Creator')
                ->options(fn () => User::query()->orderBy('name')->limit(500)->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('title')->required()->maxLength(120),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Select::make('occasion')
                ->options(self::occasions())
                ->required(),
            Select::make('status')
                ->options([
                    DuaList::STATUS_ACTIVE => 'Active',
                    DuaList::STATUS_ARCHIVED => 'Archived',
                ])
                ->required(),
            DatePicker::make('start_date'),
            DatePicker::make('end_date'),
            DateTimePicker::make('published_at'),
            FileUpload::make('cover_image_path')
                ->label('Cover image')
                ->image()
                ->directory('list-covers')
                ->visibility('public'),
            TextInput::make('dua_limit_per_person')->numeric()->minValue(1)->maxValue(35),
            Select::make('display_order')->options([
                'date' => 'Order by Date',
                'gender' => 'Order by Gender',
                'person' => 'Order by Person',
            ]),
            Select::make('email_frequency')->options([
                'every_submission' => 'Every Dua Submission',
                'daily_summary' => 'Daily Summary',
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(36),
                TextColumn::make('user.name')->label('Creator')->searchable()->sortable(),
                TextColumn::make('occasion')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('submissions_count')->label('Submissions')->sortable(),
                TextColumn::make('completed_submissions_count')->label('Completed')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    DuaList::STATUS_ACTIVE => 'Active',
                    DuaList::STATUS_ARCHIVED => 'Archived',
                ]),
                SelectFilter::make('occasion')->options(self::occasions()),
                Filter::make('created_between')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                EditAction::make(),
                Action::make('archive')
                    ->visible(fn (DuaList $record): bool => $record->isActive())
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (DuaList $record) => app(ArchiveDuaListAction::class)($record)),
                Action::make('restore')
                    ->visible(fn (DuaList $record): bool => $record->isArchived())
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (DuaList $record) => app(RestoreDuaListAction::class)($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDuaLists::route('/'),
            'edit' => Pages\EditDuaList::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function occasions(): array
    {
        return [
            'umrah' => 'Umrah',
            'hajj' => 'Hajj',
            'ramadan' => 'Ramadan',
            'safar-travel' => 'Safar / Travel',
            'wedding' => 'Wedding',
            'aqiqah' => 'Aqiqah',
            'tahajjud' => 'Tahajjud',
            'quran-khatam' => 'Quran Khatam',
            'other' => 'Other',
        ];
    }
}
