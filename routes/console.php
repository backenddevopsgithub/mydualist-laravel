<?php

use App\Domains\Notifications\Jobs\SendClosingSoonReminderJob;
use App\Domains\Notifications\Jobs\SendDailyDigestJob;
use App\Domains\Notifications\Jobs\SendListImageReminderJob;
use App\Domains\Notifications\Jobs\SendNoActivityReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scheduler:heartbeat')
    ->everyMinute()
    ->name('scheduler-heartbeat')
    ->withoutOverlapping();

Schedule::job(new SendDailyDigestJob)
    ->dailyAt(config('mydualist.notifications.daily_digest_at', '23:59'))
    ->name('send-daily-digest');

Schedule::job(new SendNoActivityReminderJob)
    ->hourly()
    ->name('send-no-activity-reminder');

Schedule::job(new SendClosingSoonReminderJob)
    ->everyThirtyMinutes()
    ->name('send-closing-soon-reminder');

Schedule::job(new SendListImageReminderJob)
    ->daily()
    ->name('send-list-image-reminder');

Schedule::command('billing:reconcile-purchases')
    ->hourly()
    ->name('billing-reconcile-purchases')
    ->withoutOverlapping();

Schedule::command('billing:health --alert')
    ->dailyAt('08:00')
    ->name('billing-health-alert')
    ->withoutOverlapping();

Schedule::command('admin:monitor-exports')
    ->everyFifteenMinutes()
    ->name('admin-monitor-exports')
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\CleanupAdminExportsJob)
    ->daily()
    ->name('admin-cleanup-exports')
    ->withoutOverlapping();
