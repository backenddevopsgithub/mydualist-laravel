<?php

namespace App\Filament\Resources\CmsPageResource\Pages;

use App\Domains\Cms\Services\SiteSeoSyncService;
use App\Filament\Resources\CmsPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(SiteSeoSyncService::class)->syncCmsPage($this->record);
    }
}
