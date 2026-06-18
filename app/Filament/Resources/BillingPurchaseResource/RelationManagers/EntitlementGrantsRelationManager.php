<?php

namespace App\Filament\Resources\BillingPurchaseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntitlementGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'entitlementGrants';

    protected static ?string $title = 'Entitlement Grants';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('entitlement_key')
            ->columns([
                TextColumn::make('entitlement_key')
                    ->label('Entitlement')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state?->value ?? '—'),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('user.email')->label('User')->placeholder('—'),
                TextColumn::make('duaList.title')->label('List')->placeholder('—'),
                TextColumn::make('granted_at')->dateTime()->sortable(),
                TextColumn::make('expires_at')->dateTime()->placeholder('Never'),
            ])
            ->defaultSort('granted_at', 'desc');
    }
}
