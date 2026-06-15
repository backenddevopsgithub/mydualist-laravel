<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly DuaSubmission $submission,
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
        $this->submission->loadMissing('duaList');

        return (new MailMessage)
            ->subject('You Just Received A Dua Request')
            ->view('mail.new-submission', [
                'ownerName' => EmailPresentation::userFirstName($notifiable),
                'requestedBy' => $this->submission->displayName(),
                'listTitle' => $this->submission->duaList->title,
                'viewSubmissionsUrl' => EmailPresentation::listSubmissionsUrl($this->submission->duaList),
            ]);
    }
}
