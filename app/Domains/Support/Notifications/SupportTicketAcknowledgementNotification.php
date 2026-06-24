<?php

namespace App\Domains\Support\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketAcknowledgementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We received your support request')
            ->view('mail.support-ticket-acknowledgement', [
                'ticket' => $this->ticket,
                'firstName' => $this->ticket->first_name,
            ]);
    }
}
