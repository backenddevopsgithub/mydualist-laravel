<?php

namespace App\Filament\Resources\SeoMetadataResource\Pages;

use App\Domains\Cms\Services\SiteSeoSyncService;
use App\Filament\Resources\SeoMetadataResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSeoMetadata extends ListRecords
{
    protected static string $resource = SeoMetadataResource::class;

    public function mount(): void
    {
        parent::mount();

        app(SiteSeoSyncService::class)->sync();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncPages')
                ->label('Sync site pages')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Registers any missing static and CMS pages in this list. Existing SEO values are not overwritten.')
                ->action(function (): void {
                    $created = app(SiteSeoSyncService::class)->sync();

                    Notification::make()
                        ->title($created > 0 ? "{$created} page(s) added" : 'All pages are already registered')
                        ->success()
                        ->send();
                }),
        ];
    }
}
