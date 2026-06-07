<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DuaSuggestionResource\Pages;
use App\Models\DuaSuggestion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DuaSuggestionResource extends Resource
{
    protected static ?string $model = DuaSuggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'CMS & SEO';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->maxLength(255),
            Select::make('category')
                ->options([
                    'hajj' => 'Hajj',
                    'umrah' => 'Umrah',
                    'ramadan' => 'Ramadan',
                    'family' => 'Family',
                    'health' => 'Health',
                    'forgiveness' => 'Forgiveness',
                    'guidance' => 'Guidance',
                    'other' => 'Other',
                ])
                ->searchable()
                ->required(),
            Textarea::make('content')->required()->rows(6)->columnSpanFull(),
            Select::make('source_type')->options([
                'quran' => 'Quran',
                'sunnah' => 'Sunnah',
                'general' => 'General',
            ]),
            TextInput::make('source_reference')->maxLength(255),
            TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
            Toggle::make('is_visible')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('source_type')->badge()->placeholder('None'),
                TextColumn::make('source_reference')->searchable()->placeholder('None'),
                IconColumn::make('is_visible')->boolean()->sortable(),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'hajj' => 'Hajj',
                    'umrah' => 'Umrah',
                    'ramadan' => 'Ramadan',
                    'family' => 'Family',
                    'health' => 'Health',
                    'forgiveness' => 'Forgiveness',
                    'guidance' => 'Guidance',
                    'other' => 'Other',
                ]),
                SelectFilter::make('source_type')->options([
                    'quran' => 'Quran',
                    'sunnah' => 'Sunnah',
                    'general' => 'General',
                ]),
                TernaryFilter::make('is_visible'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDuaSuggestions::route('/'),
            'create' => Pages\CreateDuaSuggestion::route('/create'),
            'edit' => Pages\EditDuaSuggestion::route('/{record}/edit'),
        ];
    }
}
