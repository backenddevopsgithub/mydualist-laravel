<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\UserEntitlement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('first_name')->maxLength(60),
            TextInput::make('last_name')->maxLength(60),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255),
            Select::make('role')
                ->options([
                    UserRole::User->value => 'User',
                    UserRole::Admin->value => 'Admin',
                ])
                ->required(),
            Select::make('status')
                ->options([
                    UserStatus::Active->value => 'Active',
                    UserStatus::Suspended->value => 'Suspended',
                    UserStatus::Banned->value => 'Banned',
                ])
                ->required(),
            DateTimePicker::make('email_verified_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['duaLists', 'duaSubmissions'])->with('entitlements'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('role')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('dua_lists_count')->label('Lists')->sortable(),
                TextColumn::make('dua_submissions_count')->label('Submissions')->sortable(),
                IconColumn::make('premium')
                    ->label('Premium')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->entitlements->contains(fn (UserEntitlement $entitlement): bool => $entitlement->key === UserEntitlement::KEY_PREMIUM && $entitlement->active)),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::User->value => 'User',
                    UserRole::Admin->value => 'Admin',
                ]),
                SelectFilter::make('status')->options([
                    UserStatus::Active->value => 'Active',
                    UserStatus::Suspended->value => 'Suspended',
                    UserStatus::Banned->value => 'Banned',
                ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('activate')
                    ->visible(fn (User $record): bool => $record->status !== UserStatus::Active)
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->forceFill(['status' => UserStatus::Active])->save()),
                Action::make('suspend')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Active)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->forceFill(['status' => UserStatus::Suspended])->save()),
                Action::make('grantPremium')
                    ->label('Grant Premium')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->entitlements()->updateOrCreate(
                        ['key' => UserEntitlement::KEY_PREMIUM, 'reference' => 'admin-'.$record->id],
                        ['active' => true, 'source' => 'admin', 'unlocked_at' => now(), 'expires_at' => null],
                    )),
                Action::make('resetVerification')
                    ->label('Reset Verification')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->forceFill(['email_verified_at' => null])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
