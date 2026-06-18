<?php

namespace App\Filament\Resources\ReportedDuaSubmissionResource\Pages;

use App\Filament\Resources\ReportedDuaSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListReportedDuaSubmissions extends ListRecords
{
    protected static string $resource = ReportedDuaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
