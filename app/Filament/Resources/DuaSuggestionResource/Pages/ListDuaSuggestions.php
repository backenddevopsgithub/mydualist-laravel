<?php

namespace App\Filament\Resources\DuaSuggestionResource\Pages;

use App\Filament\Resources\DuaSuggestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDuaSuggestions extends ListRecords
{
    protected static string $resource = DuaSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
