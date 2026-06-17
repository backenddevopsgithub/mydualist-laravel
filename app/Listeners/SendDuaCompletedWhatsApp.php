<?php

namespace App\Listeners;

use App\Events\DuaSubmissionCompleted;
use App\Jobs\SendWhatsAppCompletionNotificationJob;

class SendDuaCompletedWhatsApp
{
    public function handle(DuaSubmissionCompleted $event): void
    {
        SendWhatsAppCompletionNotificationJob::dispatch($event->submission->id);
    }
}
