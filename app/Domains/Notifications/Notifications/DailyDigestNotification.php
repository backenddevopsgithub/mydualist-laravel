<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DailyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, DuaSubmission>  $submissions
     */
    public function __construct(
        private readonly DuaList $duaList,
        private readonly Collection $submissions,
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
        $previewSubmissions = $this->submissions
            ->sortByDesc('created_at')
            ->take(3)
            ->values();

        $submitterRows = $this->submissions
            ->map(fn (DuaSubmission $submission): array => [
                'name' => $submission->displayName(),
                'preview' => Str::limit($submission->content, 120),
            ])
            ->all();

        return (new MailMessage)
            ->subject('You Just Received A Dua Request')
            ->view('mail.daily-digest', [
                'ownerName' => EmailPresentation::userFirstName($notifiable),
                'listTitle' => $this->duaList->title,
                'submissionCount' => $this->submissions->count(),
                'submitterRows' => $submitterRows,
                'previewSubmissions' => $previewSubmissions,
                'viewSubmissionsUrl' => EmailPresentation::listSubmissionsUrl($this->duaList),
            ]);
    }
}
