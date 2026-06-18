<?php

namespace App\Services;

use App\Domains\Notifications\Notifications\DailyDigestNotification;
use App\Models\DuaSubmission;
use App\Models\EmailSendLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class EmailMetricsService extends Service
{
    public function emailsSentToday(): int
    {
        return EmailSendLog::query()
            ->where('status', EmailSendLog::STATUS_SENT)
            ->where('sent_at', '>=', now()->startOfDay())
            ->count();
    }

    public function failedEmailsToday(): int
    {
        return EmailSendLog::query()
            ->where('status', EmailSendLog::STATUS_FAILED)
            ->where('sent_at', '>=', now()->startOfDay())
            ->count();
    }

    public function lastEmailSentAt(): ?CarbonInterface
    {
        $sentAt = EmailSendLog::query()
            ->where('status', EmailSendLog::STATUS_SENT)
            ->max('sent_at');

        return $sentAt ? Carbon::parse($sentAt) : null;
    }

    public function dailyDigestsSentToday(): int
    {
        return EmailSendLog::query()
            ->where('notification_class', DailyDigestNotification::class)
            ->where('status', EmailSendLog::STATUS_SENT)
            ->where('sent_at', '>=', now()->startOfDay())
            ->count();
    }

    public function pendingDigestSubmissions(): int
    {
        return DuaSubmission::query()
            ->pendingDigest()
            ->visible()
            ->whereHas('duaList', fn ($query) => $query->where('email_frequency', 'daily_summary'))
            ->count();
    }

    public function lastDigestSentAt(): ?CarbonInterface
    {
        $sentAt = EmailSendLog::query()
            ->where('notification_class', DailyDigestNotification::class)
            ->where('status', EmailSendLog::STATUS_SENT)
            ->max('sent_at');

        return $sentAt ? Carbon::parse($sentAt) : null;
    }

    /**
     * @return array{
     *     emails_sent_today: int,
     *     daily_digests_sent_today: int,
     *     pending_digest_submissions: int,
     *     failed_emails_today: int,
     *     last_email_sent_at: ?string,
     *     last_digest_sent_at: ?string,
     * }
     */
    public function dashboardWidgetMetrics(): array
    {
        return app(AdminDashboardCacheService::class)->remember(
            'email_health',
            fn (): array => [
                'emails_sent_today' => $this->emailsSentToday(),
                'daily_digests_sent_today' => $this->dailyDigestsSentToday(),
                'pending_digest_submissions' => $this->pendingDigestSubmissions(),
                'failed_emails_today' => $this->failedEmailsToday(),
                'last_email_sent_at' => $this->lastEmailSentAt()?->toIso8601String(),
                'last_digest_sent_at' => $this->lastDigestSentAt()?->toIso8601String(),
            ],
        );
    }
}
