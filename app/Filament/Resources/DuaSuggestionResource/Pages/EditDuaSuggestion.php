<?php

namespace App\Filament\Resources\DuaSuggestionResource\Pages;

use App\Filament\Resources\DuaSuggestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDuaSuggestion extends EditRecord
{
    protected static string $resource = DuaSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
