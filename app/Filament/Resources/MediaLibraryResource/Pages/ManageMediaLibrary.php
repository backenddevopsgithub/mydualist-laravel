<?php

namespace App\Filament\Resources\MediaLibraryResource\Pages;

use App\Filament\Resources\MediaLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMediaLibrary extends ManageRecords
{
    protected static string $resource = MediaLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Upload Media'),
        ];
    }
}
