<?php

namespace App\Filament\Resources;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Auth\Actions\ActivateUserAction;
use App\Domains\Auth\Actions\ResetEmailVerificationAction;
use App\Domains\Auth\Actions\SuspendUserAction;
use App\Domains\Billing\Actions\GrantUserPlanAction;
use App\Enums\EntitlementProductType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

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
            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->maxLength(255),
            Select::make('role')
                ->options([
                    UserRole::User->value => 'Subscriber',
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
            DateTimePicker::make('email_verified_at')
                ->label('Email verification status')
                ->helperText('Set a date to mark the email as verified, or clear to mark as unverified.'),
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
                    ->state(fn (User $record): bool => app(UserEntitlementService::class)->hasPremium($record)),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::User->value => 'Subscriber',
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
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('info')
                    ->visible(fn (User $record): bool => static::canImpersonateUser($record))
                    ->requiresConfirmation()
                    ->modalDescription('You will be signed in as this user on the member dashboard. Sensitive actions remain disabled until you stop impersonating.')
                    ->url(fn (User $record): string => route('impersonate', $record)),
                Action::make('activate')
                    ->visible(fn (User $record): bool => $record->status !== UserStatus::Active)
                    ->requiresConfirmation()
                    ->action(fn (User $record) => app(ActivateUserAction::class)($record)),
                Action::make('suspend')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Active)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => app(SuspendUserAction::class)($record)),
                Action::make('grantPlan')
                    ->label('Grant Plan')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->visible(fn (): bool => ! Impersonation::isActive())
                    ->authorize(fn (User $record): bool => auth()->user()?->isAdmin() ?? false)
                    ->form([
                        Select::make('product')
                            ->label('Plan')
                            ->options(EntitlementProductType::options())
                            ->required()
                            ->live(),
                        Select::make('dua_list_id')
                            ->label('Dua list')
                            ->options(fn (User $record): array => $record->duaLists()->orderBy('title')->pluck('title', 'id')->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => self::grantPlanRequiresList($get('product')))
                            ->required(fn (Get $get): bool => self::grantPlanRequiresList($get('product'))),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('This will create a billing purchase, grant entitlements, and record your admin action.')
                    ->action(function (User $record, array $data): void {
                        /** @var User $admin */
                        $admin = auth()->user();

                        try {
                            app(GrantUserPlanAction::class)(
                                $record,
                                EntitlementProductType::from($data['product']),
                                $admin,
                                $data['dua_list_id'] ?? null,
                            );

                            Notification::make()
                                ->title('Plan granted')
                                ->success()
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title('Plan grant failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('resetVerification')
                    ->label('Reset Verification')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => app(ResetEmailVerificationAction::class)($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    private static function grantPlanRequiresList(?string $product): bool
    {
        if ($product === null) {
            return false;
        }

        return EntitlementProductType::from($product)->requiresList();
    }

    private static function canImpersonateUser(User $record): bool
    {
        $admin = auth()->user();

        if (! $admin instanceof User) {
            return false;
        }

        return $admin->canImpersonate()
            && $record->canBeImpersonated()
            && $record->id !== $admin->id
            && ! Impersonation::isActive();
    }
}
