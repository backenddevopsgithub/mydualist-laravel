<?php

namespace App\Filament\Resources\DuaListResource\Pages;

use App\Filament\Resources\DuaListResource;
use Filament\Resources\Pages\ListRecords;

class ListDuaLists extends ListRecords
{
    protected static string $resource = DuaListResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
