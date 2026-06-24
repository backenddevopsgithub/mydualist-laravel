<?php

namespace App\Domains\Support\Actions;

use App\Actions\Action;
use App\Domains\Support\Notifications\SupportTicketAcknowledgementNotification;
use App\Domains\Support\Notifications\SupportTicketReceivedNotification;
use App\Domains\Support\Services\SupportNotificationSettings;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Notification;

class NotifySupportTicketCreatedAction extends Action
{
    public function __construct(
        private readonly SupportNotificationSettings $settings,
    ) {}

    public function handle(mixed ...$args): mixed
    {
        /** @var SupportTicket $ticket */
        $ticket = $args[0];

        $recipients = $this->settings->recipients();

        foreach ($recipients as $email) {
            Notification::route('mail', $email)
                ->notify(new SupportTicketReceivedNotification($ticket));
        }

        Notification::route('mail', $ticket->email)
            ->notify(new SupportTicketAcknowledgementNotification($ticket));

        return null;
    }
}
