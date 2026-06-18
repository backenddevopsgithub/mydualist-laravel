<?php

namespace App\Filament\Concerns;

use App\Enums\AdminExportType;
use App\Exceptions\AdminExportDuplicateException;
use App\Exceptions\AdminExportQueueException;
use App\Exceptions\AdminExportRateLimitException;
use App\Models\User;
use App\Services\AdminExportService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

trait HasAnalyticsExport
{
    abstract protected function analyticsExportType(): AdminExportType;

    /**
     * @return array<string, mixed>
     */
    protected function analyticsExportFilters(): array
    {
        return $this->getAnalyticsFilters();
    }

    protected function exportTableAction(): Action
    {
        return Action::make('exportCsv')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (): void {
                /** @var User $user */
                $user = auth()->user();

                try {
                    app(AdminExportService::class)->queue(
                        $user,
                        $this->analyticsExportType(),
                        $this->analyticsExportFilters(),
                    );

                    Notification::make()
                        ->title('Export queued')
                        ->body('You will be notified when your CSV export is ready to download.')
                        ->success()
                        ->send();
                } catch (AdminExportDuplicateException|AdminExportRateLimitException|AdminExportQueueException $exception) {
                    Notification::make()
                        ->title('Export not queued')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
