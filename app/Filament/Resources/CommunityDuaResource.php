<?php

namespace App\Filament\Resources;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Filament\Resources\CommunityDuaResource\Pages;
use App\Models\CommunityDua;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommunityDuaResource extends Resource
{
    protected static ?string $model = CommunityDua::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Community Duas';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest())
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('display_name')
                    ->label('Name')
                    ->state(fn (CommunityDua $record): string => $record->displayName()),
                TextColumn::make('email')->searchable(),
                TextColumn::make('gender')->badge(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('completion_count')
                    ->label('Completions')
                    ->formatStateUsing(fn (CommunityDua $record): string => "{$record->completion_count}/{$record->required_completions}"),
                TextColumn::make('required_completions')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
                TextColumn::make('report_reason')->badge()->placeholder('None'),
                TextColumn::make('content')->limit(50)->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    CommunityDuaType::Free->value => 'Free',
                    CommunityDuaType::Paid->value => 'Paid',
                ]),
                SelectFilter::make('status')->options([
                    CommunityDuaStatus::PendingPayment->value => 'Pending payment',
                    CommunityDuaStatus::Active->value => 'Active',
                    CommunityDuaStatus::Completed->value => 'Completed',
                    CommunityDuaStatus::Reported->value => 'Reported',
                ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunityDuas::route('/'),
            'view' => Pages\ViewCommunityDua::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
