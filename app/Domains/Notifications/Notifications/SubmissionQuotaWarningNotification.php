<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionQuotaWarningNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Your Dua List is Full – Time to Upgrade for More Requests!')
            ->view('mail.submission-quota-warning', [
                'listAuthor' => EmailPresentation::userFirstName($notifiable),
                'listName' => $this->duaList->title,
                'upgradeUrl' => EmailPresentation::upgradeUrl(),
            ]);
    }
}
