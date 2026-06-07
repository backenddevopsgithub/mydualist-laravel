<?php

namespace App\Filament\Resources\DuaListResource\Pages;

use App\Filament\Resources\DuaListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDuaList extends EditRecord
{
    protected static string $resource = DuaListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
