<?php

namespace App\Filament\Resources;

use App\Enums\BillingPurchaseStatus;
use App\Filament\Resources\BillingPurchaseResource\Concerns\InteractsWithBillingPurchaseActions;
use App\Filament\Resources\BillingPurchaseResource\Pages;
use App\Filament\Resources\BillingPurchaseResource\RelationManagers;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillingPurchaseResource extends Resource
{
    use InteractsWithBillingPurchaseActions;

    protected static ?string $model = BillingPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Purchases';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['product', 'user'])
                ->latest())
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->state(fn (BillingPurchase $record): string => $record->provider())
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'stripe' => 'info',
                        'woocommerce' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('amount_minor')
                    ->label('Amount')
                    ->money(fn (BillingPurchase $record): string => strtoupper($record->currency), divideBy: 100)
                    ->sortable(),
                TextColumn::make('currency')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BillingPurchaseStatus $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('fulfillment_status')
                    ->label('Fulfillment')
                    ->badge()
                    ->state(fn (BillingPurchase $record): string => $record->fulfillmentStatus())
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'fulfilled' => 'success',
                        'unfulfilled' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(BillingPurchaseStatus::cases())
                        ->mapWithKeys(fn (BillingPurchaseStatus $status): array => [
                            $status->value => str($status->value)->headline()->toString(),
                        ])
                        ->all()),
                SelectFilter::make('provider')
                    ->options([
                        'stripe' => 'Stripe',
                        'woocommerce' => 'WooCommerce',
                        'manual' => 'Manual',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $provider): Builder => $query->provider($provider),
                    )),
                SelectFilter::make('billing_product_id')
                    ->label('Product')
                    ->options(fn (): array => BillingProduct::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                TernaryFilter::make('fulfilled')
                    ->label('Fulfilled')
                    ->placeholder('All')
                    ->trueLabel('Fulfilled')
                    ->falseLabel('Unfulfilled')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('fulfilled_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('fulfilled_at'),
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
            ->actions(static::billingPurchaseTableActions())
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EntitlementGrantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillingPurchases::route('/'),
            'view' => Pages\ViewBillingPurchase::route('/{record}'),
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

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('viewAny', BillingPurchase::class);
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view', $record) ?? false;
    }
}
