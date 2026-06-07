<?php

namespace App\Filament\Resources\DuaSubmissionResource\Pages;

use App\Filament\Resources\DuaSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDuaSubmission extends EditRecord
{
    protected static string $resource = DuaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
