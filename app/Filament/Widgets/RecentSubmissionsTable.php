<?php

namespace App\Filament\Widgets;

use App\Models\DuaSubmission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSubmissionsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return DuaSubmission::query()
            ->with('duaList.user')
            ->latest()
            ->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Submission Activity')
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('display_name')
                    ->label('Name')
                    ->state(fn (DuaSubmission $record): string => $record->displayName()),
                TextColumn::make('duaList.title')
                    ->label('List')
                    ->limit(32),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
