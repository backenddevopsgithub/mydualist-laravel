<?php

namespace App\Domains\Support\Notifications;

use App\Models\SupportTicket;
use App\Support\SupportTicketReasons;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReceivedNotification extends Notification implements ShouldQueue
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
        $reason = SupportTicketReasons::labels()[$this->ticket->reason] ?? $this->ticket->reason;

        return (new MailMessage)
            ->subject('New Support Request Received')
            ->view('mail.support-ticket-received', [
                'ticket' => $this->ticket,
                'reasonLabel' => $reason,
                'submittedAt' => $this->ticket->created_at?->timezone(config('app.timezone'))->format('j M Y, g:i A T'),
            ]);
    }
}
