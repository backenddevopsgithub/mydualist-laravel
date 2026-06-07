<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StripePaymentResource\Pages;
use App\Models\StripePayment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StripePaymentResource extends Resource
{
    protected static ?string $model = StripePayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('stripe_checkout_session_id')->required()->maxLength(255),
            TextInput::make('stripe_payment_intent_id')->maxLength(255),
            TextInput::make('stripe_event_id')->maxLength(255),
            TextInput::make('amount_total')->numeric()->minValue(0),
            TextInput::make('currency')->maxLength(8),
            Select::make('status')->options([
                StripePayment::STATUS_PENDING => 'Pending',
                StripePayment::STATUS_PAID => 'Paid',
            ])->required(),
            DateTimePicker::make('paid_at'),
            KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->latest())
            ->columns([
                TextColumn::make('user.email')->label('User')->searchable()->sortable()->placeholder('Guest'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('amount_total')
                    ->label('Amount')
                    ->money(fn (StripePayment $record): string => strtoupper($record->currency ?: 'usd'), divideBy: 100)
                    ->sortable(),
                TextColumn::make('stripe_checkout_session_id')->label('Checkout Session')->searchable()->limit(28),
                TextColumn::make('stripe_payment_intent_id')->label('Payment Intent')->searchable()->limit(28),
                TextColumn::make('paid_at')->dateTime()->sortable()->placeholder('Not paid'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    StripePayment::STATUS_PENDING => 'Pending',
                    StripePayment::STATUS_PAID => 'Paid',
                ]),
                Filter::make('paid_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('paid_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('paid_at', '<=', $date))),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStripePayments::route('/'),
            'edit' => Pages\EditStripePayment::route('/{record}/edit'),
        ];
    }
}
