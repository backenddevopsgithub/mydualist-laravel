<?php

namespace App\Domains\Onboarding\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

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
            ->subject('Your My Dua List verification code')
            ->greeting('Assalamu alaikum')
            ->line('Use this code to continue creating your first dua list:')
            ->line($this->code)
            ->line('If you did not request this, you can ignore this email.');
    }
}
