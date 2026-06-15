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
}
