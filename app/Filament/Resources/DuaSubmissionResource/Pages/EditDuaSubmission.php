<?php

namespace App\Filament\Resources\DuaSubmissionResource\Pages;

use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Resources\DuaSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDuaSubmission extends EditRecord
{
    protected static string $resource = DuaSubmissionResource::class;

    private ?DuaSubmissionStatus $pendingStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['status'])) {
            $this->pendingStatus = DuaSubmissionStatus::from($data['status']);
            unset($data['status']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingStatus === null) {
            return;
        }

        app(TransitionDuaSubmissionStatusAction::class)($this->record->fresh(), $this->pendingStatus);
        $this->pendingStatus = null;
    }
}
