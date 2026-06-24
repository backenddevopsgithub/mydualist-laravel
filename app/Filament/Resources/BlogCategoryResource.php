<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogCategoryResource\Pages;
use App\Models\BlogCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('slug')->required()->maxLength(120)->unique(ignoreRecord: true),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
            Toggle::make('show_in_resources_filter')
                ->label('Show in Dua Resources filters')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('posts_count')->counts('posts')->label('Posts'),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('show_in_resources_filter')->label('Filter tab')->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
