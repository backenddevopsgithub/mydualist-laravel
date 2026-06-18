<?php

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\Services\ReminderEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendListImageReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ReminderEmailService $reminders): void
    {
        $reminders->sendListImageReminders();
    }
}
