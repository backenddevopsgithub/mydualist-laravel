<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoMetadataResource\Pages;
use App\Models\SeoMetadata;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeoMetadataResource extends Resource
{
    protected static ?string $model = SeoMetadata::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'CMS & SEO';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Target')->schema([
                TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('scope')->options([
                    'global' => 'Global',
                    'route' => 'Route',
                    'cms' => 'CMS Page',
                    'public_list' => 'Public List',
                ])->required(),
                TextInput::make('route_name')->maxLength(255),
            ])->columns(3),
            Section::make('Metadata')->schema([
                TextInput::make('meta_title')->maxLength(255),
                Textarea::make('meta_description')->rows(3),
                TextInput::make('og_title')->maxLength(255),
                Textarea::make('og_description')->rows(3),
                FileUpload::make('og_image_path')->image()->directory('seo-og')->visibility('public'),
                Select::make('twitter_card')->options([
                    'summary' => 'Summary',
                    'summary_large_image' => 'Summary Large Image',
                ])->required(),
                TextInput::make('canonical_url')->url()->maxLength(255),
                Toggle::make('noindex'),
                Toggle::make('nofollow'),
                KeyValue::make('metadata')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('scope')->badge()->sortable(),
                TextColumn::make('route_name')->searchable()->placeholder('None'),
                TextColumn::make('meta_title')->limit(40),
                IconColumn::make('noindex')->boolean(),
                IconColumn::make('nofollow')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoMetadata::route('/'),
            'create' => Pages\CreateSeoMetadata::route('/create'),
            'edit' => Pages\EditSeoMetadata::route('/{record}/edit'),
        ];
    }
}
