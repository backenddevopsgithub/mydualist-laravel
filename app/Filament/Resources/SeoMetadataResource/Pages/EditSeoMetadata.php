<?php

namespace App\Filament\Resources\SeoMetadataResource\Pages;

use App\Domains\Cms\Services\SiteSeoSyncService;
use App\Filament\Resources\SeoMetadataResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoMetadata extends EditRecord
{
    protected static string $resource = SeoMetadataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => in_array($this->record->scope, ['blog', 'global'], true)),
        ];
    }

    protected function afterSave(): void
    {
        app(SiteSeoSyncService::class)->applyToCmsPage($this->record);
    }
}
