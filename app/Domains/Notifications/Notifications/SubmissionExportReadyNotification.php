<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Support\EmailPresentation;
use App\Models\AdminExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AdminExport $export,
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
        $downloadUrl = $this->export->downloadUrl();

        return (new MailMessage)
            ->subject('Your dua list export is ready')
            ->view('mail.submission-export-ready', [
                'recipientName' => EmailPresentation::userFirstName($notifiable),
                'listTitle' => (string) data_get($this->export->filters, 'dua_list_title', 'your list'),
                'rowCount' => (int) $this->export->row_count,
                'downloadUrl' => $downloadUrl ?? EmailPresentation::dashboardUrl(),
                'expiresInDays' => (int) config('mydualist.admin_exports.download_url_ttl_days', 7),
            ]);
    }
}
