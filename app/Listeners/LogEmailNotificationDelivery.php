<?php

namespace App\Listeners;

use App\Models\EmailSendLog;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class LogEmailNotificationDelivery
{
    public function handleSent(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        EmailSendLog::query()->create([
            'notification_class' => $event->notification::class,
            'recipient_email' => $this->recipientEmail($event->notifiable),
            'status' => EmailSendLog::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function handleFailed(NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        EmailSendLog::query()->create([
            'notification_class' => $event->notification::class,
            'recipient_email' => $this->recipientEmail($event->notifiable),
            'status' => EmailSendLog::STATUS_FAILED,
            'error_message' => $event->data['exception'] ?? null,
            'sent_at' => now(),
        ]);
    }

    private function recipientEmail(object $notifiable): string
    {
        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $route = $notifiable->routeNotificationForMail();

            return is_string($route) ? $route : (string) ($notifiable->email ?? 'unknown');
        }

        return (string) ($notifiable->email ?? 'unknown');
    }
}
