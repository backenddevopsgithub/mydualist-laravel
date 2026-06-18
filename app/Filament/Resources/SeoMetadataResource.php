<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoMetadataResource\Pages;
use App\Models\SeoMetadata;
use App\Support\Seo\SeoPageRegistry;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SeoMetadataResource extends Resource
{
    protected static ?string $model = SeoMetadata::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'CMS & SEO';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Site SEO';

    protected static ?string $modelLabel = 'page SEO';

    protected static ?string $pluralModelLabel = 'Site SEO';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Page')
                ->description('These values control the &lt;title&gt;, meta description, Open Graph tags, and canonical URL shown to search engines and social platforms.')
                ->schema([
                    Placeholder::make('page_label')
                        ->label('Page')
                        ->content(fn (?SeoMetadata $record): string => $record ? SeoPageRegistry::label($record) : '—'),
                    Placeholder::make('page_url')
                        ->label('Public URL')
                        ->content(fn (?SeoMetadata $record): string => $record ? (SeoPageRegistry::url($record) ?: '—') : '—'),
                    Placeholder::make('edit_hint')
                        ->label('Note')
                        ->content(fn (?SeoMetadata $record): string => $record ? (SeoPageRegistry::editHint($record) ?: '—') : '—')
                        ->visible(fn (?SeoMetadata $record): bool => $record !== null && SeoPageRegistry::editHint($record) !== null),
                ])
                ->columns(2),
            Section::make('Search & social metadata')->schema([
                TextInput::make('meta_title')
                    ->label('Meta title')
                    ->helperText('Shown in browser tabs and Google search results. Keep under ~60 characters.')
                    ->maxLength(255),
                Textarea::make('meta_description')
                    ->label('Meta description')
                    ->helperText('Short summary for search results. Aim for ~150–160 characters.')
                    ->rows(3),
                TextInput::make('og_title')
                    ->label('Open Graph title')
                    ->helperText('Title when shared on Facebook, WhatsApp, etc. Defaults to meta title if empty.')
                    ->maxLength(255),
                Textarea::make('og_description')
                    ->label('Open Graph description')
                    ->rows(3),
                FileUpload::make('og_image_path')
                    ->label('Social share image')
                    ->image()
                    ->directory('seo-og')
                    ->visibility('public'),
                Select::make('twitter_card')->options([
                    'summary' => 'Summary',
                    'summary_large_image' => 'Summary large image',
                ])->required(),
                TextInput::make('canonical_url')
                    ->label('Canonical URL')
                    ->helperText('Preferred URL for this page. Leave blank to use the default public URL.')
                    ->url()
                    ->maxLength(255),
                Toggle::make('noindex')
                    ->label('Hide from search engines (noindex)')
                    ->helperText('Discourages Google and other crawlers from indexing this page.'),
                Toggle::make('nofollow'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('scope')->orderBy('key'))
            ->columns([
                TextColumn::make('page')
                    ->label('Page')
                    ->state(fn (SeoMetadata $record): string => SeoPageRegistry::label($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('key', 'like', "%{$search}%")
                            ->orWhere('meta_title', 'like', "%{$search}%");
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('key', $direction)),
                TextColumn::make('scope')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'route' => 'Static page',
                        'cms' => 'CMS page',
                        'blog' => 'Blog post',
                        default => str($state)->headline()->toString(),
                    })
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->state(fn (SeoMetadata $record): ?string => SeoPageRegistry::url($record))
                    ->url(fn (SeoMetadata $record): ?string => SeoPageRegistry::url($record), shouldOpenInNewTab: true)
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('meta_title')
                    ->label('Meta title')
                    ->limit(40)
                    ->placeholder('Not set'),
                TextColumn::make('meta_description')
                    ->label('Meta description')
                    ->limit(50)
                    ->placeholder('Not set')
                    ->toggleable(),
                IconColumn::make('noindex')
                    ->label('Noindex')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('Type')
                    ->options([
                        'route' => 'Static pages',
                        'cms' => 'CMS pages',
                        'blog' => 'Blog posts',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View live')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SeoMetadata $record): ?string => SeoPageRegistry::url($record), shouldOpenInNewTab: true)
                    ->visible(fn (SeoMetadata $record): bool => SeoPageRegistry::url($record) !== null),
                EditAction::make(),
            ])
            ->emptyStateHeading('No site pages registered yet')
            ->emptyStateDescription('Use “Sync site pages” to register homepage, CMS pages, and other public URLs for SEO editing.');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoMetadata::route('/'),
            'edit' => Pages\EditSeoMetadata::route('/{record}/edit'),
        ];
    }
}
