<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriptionResource\Pages;
use App\Models\NewsletterSubscription;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriptionResource extends Resource
{
    protected static ?string $model = NewsletterSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Newsletter';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('email')->email()->required()->maxLength(255),
            TextInput::make('source')->required()->maxLength(120),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest())
            ->columns([
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('source')->badge()->sortable(),
                TextColumn::make('created_at')->label('Subscribed At')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('subscribed_between')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'w');
                        fputcsv($handle, ['ID', 'Email', 'Source', 'Subscribed At']);

                        NewsletterSubscription::query()
                            ->orderBy('id')
                            ->chunk(200, function ($rows) use ($handle): void {
                                foreach ($rows as $row) {
                                    fputcsv($handle, [
                                        $row->id,
                                        $row->email,
                                        $row->source,
                                        optional($row->created_at)->toDateTimeString(),
                                    ]);
                                }
                            });

                        fclose($handle);
                    }, 'newsletter-subscriptions-'.now()->format('Y-m-d').'.csv', [
                        'Content-Type' => 'text/csv',
                    ])),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscriptions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
