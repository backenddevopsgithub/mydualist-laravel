<?php

namespace App\Filament\Pages\System;

use App\Models\FailedJob;
use App\Models\User;
use App\Policies\QueueMonitorPolicy;
use App\Services\QueueActionLogService;
use App\Services\SystemHealthService;
use App\Support\SchedulerHealth;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class QueueMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Queue Monitor';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.queue-monitor';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(QueueMonitorPolicy::class)->viewAny($user);
    }

    public function getHealthStats(): array
    {
        $health = app(SystemHealthService::class);
        $schedulerStatus = $health->schedulerStatus();
        $schedulerLastRun = $health->schedulerLastRun();

        return [
            'connection' => $health->queueConnection(),
            'pending' => $health->pendingJobsCount(),
            'failed' => $health->failedJobsCount(),
            'scheduler_status' => SchedulerHealth::statusLabel($schedulerStatus),
            'scheduler_last_run' => $schedulerLastRun?->toDateTimeString() ?? 'Never',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => FailedJob::query()->orderByDesc('failed_at'))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('queue')->badge(),
                TextColumn::make('connection')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('exception')
                    ->label('Exception')
                    ->limit(80)
                    ->formatStateUsing(fn (?string $state): string => \App\Support\ExceptionSanitizer::forDisplay($state))
                    ->wrap(),
                TextColumn::make('failed_at')->label('Failed At')->dateTime()->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Failed Job Details')
                    ->modalContent(fn ($record) => view('filament.pages.partials.failed-job-details', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->canRetryFailedJob())
                    ->action(function ($record): void {
                        $this->authorizeQueueAction('retryFailedJob');

                        Artisan::call('queue:retry', ['id' => [$record->id]]);

                        $this->logQueueAction('retry_failed_job', [(string) $record->id]);

                        Notification::make()
                            ->title('Job queued for retry')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('retryAll')
                    ->label('Retry All Failed')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->canRetryAllFailedJobs())
                    ->action(function (): void {
                        $this->authorizeQueueAction('retryAllFailedJobs');

                        Artisan::call('queue:retry', ['id' => ['all']]);

                        $this->logQueueAction('retry_all_failed_jobs');

                        Notification::make()
                            ->title('All failed jobs queued for retry')
                            ->success()
                            ->send();
                    }),
                Action::make('clearFailed')
                    ->label('Clear Failed Jobs')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->canFlushFailedJobs())
                    ->action(function (): void {
                        $this->authorizeQueueAction('flushFailedJobs');

                        Artisan::call('queue:flush');

                        $this->logQueueAction('flush_failed_jobs');

                        Notification::make()
                            ->title('Failed jobs cleared')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No failed jobs')
            ->emptyStateDescription('The queue has no recorded failures.')
            ->defaultSort('failed_at', 'desc');
    }

    protected function canRetryFailedJob(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(QueueMonitorPolicy::class)->retryFailedJob($user);
    }

    protected function canRetryAllFailedJobs(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(QueueMonitorPolicy::class)->retryAllFailedJobs($user);
    }

    protected function canFlushFailedJobs(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(QueueMonitorPolicy::class)->flushFailedJobs($user);
    }

    protected function authorizeQueueAction(string $ability): void
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless(app(QueueMonitorPolicy::class)->{$ability}($user), 403);
    }

    /**
     * @param  list<int|string>  $jobIds
     */
    protected function logQueueAction(string $action, array $jobIds = []): void
    {
        /** @var User $user */
        $user = auth()->user();

        app(QueueActionLogService::class)->record(
            $user,
            $action,
            $jobIds,
            request()->ip(),
        );
    }
}
