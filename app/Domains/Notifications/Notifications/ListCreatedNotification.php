<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly DuaList $duaList,
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
        $listAuthor = EmailPresentation::userFirstName($notifiable);

        return (new MailMessage)
            ->subject("{$listAuthor}, Your Dua List is Ready – Explore Our Key Features!")
            ->view('mail.list-created', [
                'listAuthor' => $listAuthor,
                'dashboardUrl' => EmailPresentation::dashboardUrl(),
            ]);
    }
}
