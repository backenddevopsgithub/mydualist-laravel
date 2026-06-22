<?php

namespace App\Filament\Resources\DuaListResource\Pages;

use App\Filament\Pages\Analytics\DuaListAnalytics;
use App\Filament\Resources\DuaListResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDuaLists extends ListRecords
{
    protected static string $resource = DuaListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewAnalytics')
                ->label('View analytics')
                ->icon('heroicon-o-chart-bar')
                ->url(DuaListAnalytics::getUrl())
                ->color('gray'),
        ];
    }
}
