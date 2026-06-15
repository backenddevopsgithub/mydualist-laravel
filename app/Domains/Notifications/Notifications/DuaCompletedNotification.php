<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DuaCompletedNotification extends Notification implements ShouldQueue
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
        $this->submission->loadMissing('duaList.user');
        $owner = $this->submission->duaList->user;
        $duaAuthor = EmailPresentation::userFirstName($owner);

        return (new MailMessage)
            ->subject("{$duaAuthor} Just Completed Your Dua Request")
            ->view('mail.dua-completed', [
                'duaAuthor' => $duaAuthor,
                'requestedBy' => trim((string) $this->submission->first_name) ?: 'there',
                'possessivePronoun' => EmailPresentation::possessivePronoun($owner),
                'occasionLabel' => $this->submission->duaList->occasionLabel(),
                'listTitle' => $this->submission->duaList->title,
                'listImageUrl' => $this->submission->duaList->coverImageUrl(),
                'duaMessage' => $this->submission->content,
            ]);
    }
}
