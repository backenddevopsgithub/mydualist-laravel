<?php

namespace App\Filament\Resources\CommunityDuaResource\Pages;

use App\Filament\Resources\CommunityDuaResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityDua extends ViewRecord
{
    protected static string $resource = CommunityDuaResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('first_name')->label('First name'),
            TextEntry::make('last_name')->label('Last name'),
            TextEntry::make('email'),
            TextEntry::make('gender'),
            TextEntry::make('type'),
            TextEntry::make('status'),
            TextEntry::make('completion_count'),
            TextEntry::make('required_completions'),
            TextEntry::make('is_visible')->label('Visible in queue'),
            TextEntry::make('report_reason')->placeholder('None'),
            TextEntry::make('report_note')->placeholder('None'),
            TextEntry::make('content')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('fulfilled_at')->dateTime()->placeholder('Not fulfilled'),
        ]);
    }
}
