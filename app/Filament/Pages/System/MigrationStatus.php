<?php

namespace App\Filament\Pages\System;

use App\Jobs\RunMigrationValidationJob;
use App\Models\User;
use App\Policies\AnalyticsPolicy;
use App\Services\MigrationStatusService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MigrationStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Migration Status';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.migration-status';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    /**
     * @return array{passed: bool, totals: array<string, int>, failures: list<array<string, mixed>>, warnings: list<array<string, mixed>>, report_path: string|null, report_exists: bool}
     */
    public function getStatus(): array
    {
        return app(MigrationStatusService::class)->status();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runValidation')
                ->label('Run Validation')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (): void {
                    if (app()->isProduction() && config('queue.default') === 'sync') {
                        Notification::make()
                            ->title('Validation cannot run')
                            ->body('Migration validation requires an async queue connection in production.')
                            ->danger()
                            ->send();

                        return;
                    }

                    RunMigrationValidationJob::dispatch();

                    Notification::make()
                        ->title('Validation queued')
                        ->body('Migration validation has been queued and will update the cached report when complete.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
