<?php

namespace App\Filament\Resources\MediaLibraryResource\Pages;

use App\Filament\Resources\MediaLibraryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaLibraryItem extends CreateRecord
{
    protected static string $resource = MediaLibraryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return MediaLibraryResource::getUrl('index');
    }
}
