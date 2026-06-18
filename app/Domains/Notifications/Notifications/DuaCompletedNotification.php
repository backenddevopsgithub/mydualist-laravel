<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaSubmission;
use App\Support\TrackableDonationLink;
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
        $duaList = $this->submission->duaList;
        $owner = $duaList->user;
        $duaAuthor = EmailPresentation::userFirstName($owner);
        $isSalawat = $duaList->occasion === 'salawat';

        $subject = $isSalawat
            ? "{$duaAuthor} has completed your salawat request"
            : "{$duaAuthor} Just Completed Your Dua Request";

        $view = $isSalawat ? 'mail.dua-completed-salawat' : 'mail.dua-completed';

        $viewData = [
            'duaAuthor' => $duaAuthor,
            'requestedBy' => trim((string) $this->submission->first_name) ?: 'there',
            'possessivePronoun' => EmailPresentation::possessivePronoun($owner),
            'listTitle' => $duaList->title,
            'listImageUrl' => $duaList->coverImageUrl(),
            'createListUrl' => EmailPresentation::createListUrl(),
        ];

        if (! $isSalawat) {
            $viewData['occasionLabel'] = $duaList->occasionLabel();
            $viewData['duaMessage'] = $this->submission->content;

            if ($duaList->hasFundraisingContent()) {
                $viewData['fundraisingContent'] = [
                    'note' => (string) $duaList->donation_note,
                    'url' => TrackableDonationLink::forList($duaList, (string) $duaList->donation_link),
                ];
            }
        }

        return (new MailMessage)
            ->subject($subject)
            ->view($view, $viewData);
    }
}
