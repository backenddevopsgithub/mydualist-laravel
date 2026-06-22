<?php

namespace App\Filament\Resources\DuaSubmissionResource\Pages;

use App\Filament\Pages\Analytics\SubmissionAnalytics;
use App\Filament\Resources\DuaSubmissionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDuaSubmissions extends ListRecords
{
    protected static string $resource = DuaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewAnalytics')
                ->label('View analytics')
                ->icon('heroicon-o-chart-bar')
                ->url(SubmissionAnalytics::getUrl())
                ->color('gray'),
        ];
    }
}
