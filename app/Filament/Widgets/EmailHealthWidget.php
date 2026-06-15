<?php

namespace App\Filament\Widgets;

use App\Services\EmailMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $metrics = app(EmailMetricsService::class);
        $lastSentAt = $metrics->lastEmailSentAt();
        $lastDigestAt = $metrics->lastDigestSentAt();
        $failedToday = $metrics->failedEmailsToday();
        $pendingDigests = $metrics->pendingDigestSubmissions();

        return [
            Stat::make('Emails Sent Today', number_format($metrics->emailsSentToday()))
                ->description($lastSentAt
                    ? 'Last sent: '.$lastSentAt->toDateTimeString()
                    : 'No emails logged yet')
                ->color('success'),
            Stat::make('Daily Digests Today', number_format($metrics->dailyDigestsSentToday()))
                ->description($lastDigestAt
                    ? 'Last digest: '.$lastDigestAt->toDateTimeString()
                    : 'No digests logged yet')
                ->color('success'),
            Stat::make('Pending Digest Submissions', number_format($pendingDigests))
                ->description('Awaiting next daily digest run')
                ->color($pendingDigests > 0 ? 'warning' : 'success'),
            Stat::make('Failed Emails Today', number_format($failedToday))
                ->description('Logged notification delivery failures')
                ->color($failedToday > 0 ? 'danger' : 'success'),
        ];
    }
}
