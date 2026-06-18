<?php

namespace App\Filament\Resources;

use App\Domains\Billing\Services\EntitlementGrantManagementService;
use App\Enums\EntitlementProductType;
use App\Filament\Resources\EntitlementGrantResource\Pages;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class EntitlementGrantResource extends Resource
{
    protected static ?string $model = EntitlementGrant::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Entitlement Grants';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')
                ->label('User')
                ->options(fn () => User::query()->orderBy('name')->limit(500)->pluck('email', 'id'))
                ->searchable()
                ->required()
                ->live(),
            Select::make('product')
                ->label('Product')
                ->options(EntitlementProductType::options())
                ->required()
                ->live(),
            Select::make('dua_list_id')
                ->label('Dua list')
                ->options(fn (Get $get): array => DuaList::query()
                    ->when($get('user_id'), fn (Builder $query, $userId): Builder => $query->where('user_id', $userId))
                    ->orderBy('title')
                    ->limit(500)
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->visible(fn (Get $get): bool => self::selectedProductRequiresList($get('product')))
                ->required(fn (Get $get): bool => self::selectedProductRequiresList($get('product'))),
            DateTimePicker::make('expires_at')
                ->label('Expires at')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->latest('granted_at'))
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product')
                    ->label('Product')
                    ->state(fn (EntitlementGrant $record): string => $record->productLabel())
                    ->badge(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('granted_by')
                    ->label('Granted by')
                    ->state(fn (EntitlementGrant $record): string => $record->grantedByLabel()),
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->state(fn (EntitlementGrant $record): bool => $record->isActive()),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('entitlement_key')
                    ->label('Product')
                    ->options(collect(EntitlementProductType::cases())
                        ->mapWithKeys(fn (EntitlementProductType $type): array => [
                            $type->entitlementKey()->value => $type->label(),
                        ])
                        ->all()),
                TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        }),
                        false: fn (Builder $query): Builder => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                    ),
                Filter::make('created_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create grant')
                    ->visible(fn (): bool => ! Impersonation::isActive())
                    ->mutateFormDataUsing(fn (array $data): array => $data)
                    ->using(function (array $data): EntitlementGrant {
                        /** @var User $admin */
                        $admin = auth()->user();
                        $user = User::query()->findOrFail($data['user_id']);
                        $product = EntitlementProductType::from($data['product']);

                        return app(EntitlementGrantManagementService::class)->createGrant(
                            $user,
                            $product,
                            $admin,
                            $data['dua_list_id'] ?? null,
                            isset($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null,
                        );
                    }),
            ])
            ->actions([
                Action::make('extendExpiration')
                    ->label('Extend expiration')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(fn (EntitlementGrant $record): bool => $record->isActive() && ! Impersonation::isActive())
                    ->authorize('update')
                    ->form([
                        DateTimePicker::make('expires_at')
                            ->label('New expiration')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(function (EntitlementGrant $record, array $data): void {
                        static::runGrantAction(
                            fn () => app(EntitlementGrantManagementService::class)->extendExpiration(
                                $record,
                                \Illuminate\Support\Carbon::parse($data['expires_at']),
                                auth()->user(),
                            ),
                            'Grant expiration extended.',
                        );
                    }),
                Action::make('revoke')
                    ->label('Revoke grant')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EntitlementGrant $record): bool => $record->isActive() && ! Impersonation::isActive())
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->action(function (EntitlementGrant $record): void {
                        static::runGrantAction(
                            fn () => app(EntitlementGrantManagementService::class)->revokeGrant(
                                $record,
                                auth()->user(),
                            ),
                            'Grant revoked.',
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntitlementGrants::route('/'),
            'create' => Pages\CreateEntitlementGrant::route('/create'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('viewAny', EntitlementGrant::class);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', EntitlementGrant::class) ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    private static function selectedProductRequiresList(?string $product): bool
    {
        if ($product === null) {
            return false;
        }

        return EntitlementProductType::from($product)->requiresList();
    }

  /**
   * @param  callable(): EntitlementGrant  $callback
   */
    protected static function runGrantAction(callable $callback, string $successMessage): void
    {
        try {
            $callback();

            Notification::make()
                ->title($successMessage)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Action failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
