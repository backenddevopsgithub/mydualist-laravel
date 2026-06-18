<?php

namespace App\Filament\Pages\System;

use App\Models\User;
use App\Policies\AnalyticsPolicy;
use App\Services\EmailMetricsService;
use Filament\Pages\Page;

class EmailHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Email Health';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.email-health';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    /**
     * @return list<array{label: string, value: string, description?: string|null}>
     */
    public function getMetrics(): array
    {
        $metrics = app(EmailMetricsService::class);
        $lastSentAt = $metrics->lastEmailSentAt();
        $lastDigestAt = $metrics->lastDigestSentAt();

        return [
            [
                'label' => 'Emails Sent Today',
                'value' => number_format($metrics->emailsSentToday()),
                'description' => $lastSentAt ? 'Last sent: '.$lastSentAt->toDateTimeString() : 'No emails logged yet',
            ],
            [
                'label' => 'Daily Digests Today',
                'value' => number_format($metrics->dailyDigestsSentToday()),
                'description' => $lastDigestAt ? 'Last digest: '.$lastDigestAt->toDateTimeString() : 'No digests logged yet',
            ],
            [
                'label' => 'Pending Digest Submissions',
                'value' => number_format($metrics->pendingDigestSubmissions()),
                'description' => 'Awaiting next daily digest run',
            ],
            [
                'label' => 'Failed Emails Today',
                'value' => number_format($metrics->failedEmailsToday()),
                'description' => 'Logged notification delivery failures',
            ],
        ];
    }
}
