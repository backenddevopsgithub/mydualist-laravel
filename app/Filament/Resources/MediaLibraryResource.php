<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaLibraryResource\Pages;
use App\Models\MediaLibraryItem;
use App\Models\User;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MediaLibraryResource extends Resource
{
    protected static ?string $model = MediaLibraryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Media Library';

    protected static ?string $navigationLabel = 'Media Manager';

    protected static ?string $modelLabel = 'Media';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('viewAny', MediaLibraryItem::class);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('create', MediaLibraryItem::class);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('delete', MediaLibraryItem::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->maxLength(255)
                ->placeholder('Optional title'),
            SpatieMediaLibraryFileUpload::make('media')
                ->collection('default')
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->downloadable()
                ->openable()
                ->imageEditor()
                ->acceptedFileTypes([
                    'image/*',
                    'video/*',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->maxSize(51200)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['media', 'uploader'])->latest())
            ->columns([
                ImageColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(fn (MediaLibraryItem $record): ?string => $record->signedPreviewUrl('thumb') ?: $record->signedPreviewUrl())
                    ->square(),
                TextColumn::make('media.file_name')
                    ->label('Filename')
                    ->getStateUsing(fn (MediaLibraryItem $record): string => $record->getFirstMedia()?->file_name ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('media', fn (Builder $mediaQuery): Builder => $mediaQuery->where('file_name', 'like', '%'.$search.'%'));
                    }),
                TextColumn::make('media.mime_type')
                    ->label('Type')
                    ->getStateUsing(fn (MediaLibraryItem $record): string => $record->getFirstMedia()?->mime_type ?? '—')
                    ->badge(),
                TextColumn::make('media.size')
                    ->label('Size')
                    ->getStateUsing(fn (MediaLibraryItem $record): string => number_format(((int) ($record->getFirstMedia()?->size ?? 0)) / 1024, 1).' KB'),
                TextColumn::make('uploader.name')->label('Uploaded By')->placeholder('System'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('file_type')
                    ->label('File Type')
                    ->options([
                        'images' => 'Images',
                        'videos' => 'Videos',
                        'documents' => 'Documents',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'images') {
                            return $query->whereHas('media', fn (Builder $q) => $q->where('mime_type', 'like', 'image/%'));
                        }

                        if ($value === 'videos') {
                            return $query->whereHas('media', fn (Builder $q) => $q->where('mime_type', 'like', 'video/%'));
                        }

                        if ($value === 'documents') {
                            return $query->whereHas('media', fn (Builder $q) => $q->where('mime_type', 'like', 'application/%'));
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Media')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (MediaLibraryItem $record): ?string => $record->signedPreviewUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (MediaLibraryItem $record): bool => $record->getFirstMedia() !== null),
                Action::make('copyUrl')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (MediaLibraryItem $record): void {
                        Notification::make()
                            ->title('Media URL')
                            ->body($record->signedPreviewUrl() ?? '—')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->delete()),
            ])
            ->emptyStateHeading('No media files yet')
            ->emptyStateDescription('Upload images, videos, or documents to build your media library.')
            ->emptyStateActions([
                CreateAction::make()->label('Upload Media'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMediaLibrary::route('/'),
            'create' => Pages\CreateMediaLibraryItem::route('/create'),
        ];
    }
}
