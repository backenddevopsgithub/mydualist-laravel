<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject('Welcome to My Dua List')
            ->view('mail.welcome-user', [
                'dashboardUrl' => EmailPresentation::dashboardUrl(),
            ]);
    }
}
