<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ! Impersonation::isActive() && $this->canDelete()),
        ];
    }

    protected function canDelete(): bool
    {
        $record = $this->getRecord();

        if (! $record instanceof User) {
            return false;
        }

        return auth()->user()?->can('delete', $record) ?? false;
    }
}
