<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('reason')
                ->options([
                    'account' => 'Account Help',
                    'billing' => 'Billing',
                    'bug' => 'Bug Report',
                    'feature' => 'Feature Request',
                    'other' => 'Other',
                ])
                ->required(),
            TextInput::make('email')->email()->required()->maxLength(255),
            TextInput::make('first_name')->required()->maxLength(255),
            TextInput::make('surname')->required()->maxLength(255),
            Textarea::make('comments')->required()->rows(8)->columnSpanFull(),
            FileUpload::make('image_path')
                ->image()
                ->directory('support-uploads')
                ->visibility('public'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->latest())
            ->columns([
                TextColumn::make('reason')->badge()->sortable(),
                ImageColumn::make('image_path')
                    ->label('Attachment')
                    ->disk('public')
                    ->square()
                    ->visibility('public')
                    ->defaultImageUrl(null)
                    ->visibleFrom('md'),
                TextColumn::make('first_name')->searchable()->sortable(),
                TextColumn::make('surname')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Account')->searchable()->placeholder('Guest'),
                TextColumn::make('comments')->limit(80)->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('reason')->options([
                    'account' => 'Account Help',
                    'billing' => 'Billing',
                    'bug' => 'Bug Report',
                    'feature' => 'Feature Request',
                    'other' => 'Other',
                ]),
                Filter::make('created_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Action::make('viewAttachment')
                    ->label('Attachment')
                    ->icon('heroicon-o-paper-clip')
                    ->url(fn (SupportTicket $record): ?string => $record->image_path
                        ? Storage::disk('public')->url($record->image_path)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (SupportTicket $record): bool => filled($record->image_path)),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
