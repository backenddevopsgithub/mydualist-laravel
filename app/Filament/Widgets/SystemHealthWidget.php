<?php

namespace App\Filament\Widgets;

use App\Services\SystemHealthService;
use App\Support\SchedulerHealth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $health = app(SystemHealthService::class);
        $schedulerStatus = $health->schedulerStatus();
        $schedulerLastRun = $health->schedulerLastRun();
        $lastFailedJobAt = $health->lastFailedJobAt();

        return [
            Stat::make('Scheduler Status', SchedulerHealth::statusLabel($schedulerStatus))
                ->description($schedulerLastRun
                    ? 'Last heartbeat: '.$schedulerLastRun->toDateTimeString()
                    : 'No heartbeat recorded yet')
                ->color(match ($schedulerStatus) {
                    SchedulerHealth::STATUS_HEALTHY => 'success',
                    SchedulerHealth::STATUS_WARNING => 'warning',
                    default => 'danger',
                }),
            Stat::make('Queue Connection', $health->queueConnection())
                ->description('Active queue driver'),
            Stat::make('Pending Jobs', number_format($health->pendingJobsCount()))
                ->description('Jobs waiting to be processed')
                ->color($health->pendingJobsCount() > 0 ? 'warning' : 'success'),
            Stat::make('Failed Jobs', number_format($health->failedJobsCount()))
                ->description($lastFailedJobAt
                    ? 'Last failure: '.$lastFailedJobAt->toDateTimeString()
                    : 'No failed jobs recorded')
                ->color($health->failedJobsCount() > 0 ? 'danger' : 'success'),
        ];
    }
}
