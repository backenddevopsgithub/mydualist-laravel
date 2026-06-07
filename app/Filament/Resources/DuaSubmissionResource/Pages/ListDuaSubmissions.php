<?php

namespace App\Filament\Resources\DuaSubmissionResource\Pages;

use App\Filament\Resources\DuaSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListDuaSubmissions extends ListRecords
{
    protected static string $resource = DuaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
