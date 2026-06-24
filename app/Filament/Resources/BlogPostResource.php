<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('blog_category_id')
                ->label('Category')
                ->options(fn () => BlogCategory::query()->ordered()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Textarea::make('excerpt')->rows(3)->maxLength(500)->columnSpanFull(),
            RichEditor::make('content')->required()->columnSpanFull(),
            Repeater::make('faqs')
                ->label('FAQs')
                ->schema([
                    TextInput::make('question')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('answer')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->reorderable()
                ->collapsible()
                ->columnSpanFull(),
            FileUpload::make('featured_image')
                ->label('Featured image')
                ->image()
                ->directory('blog-images')
                ->visibility('public'),
            TextInput::make('read_time_minutes')->numeric()->default(5)->required(),
            Toggle::make('is_published')->default(true),
            DateTimePicker::make('published_at')->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category')->latest('published_at'))
            ->columns([
                ImageColumn::make('featured_image')->label('Image')->square(),
                TextColumn::make('title')->searchable()->sortable()->limit(40),
                TextColumn::make('category.name')->label('Category')->sortable(),
                IconColumn::make('is_published')->boolean()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('blog_category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
