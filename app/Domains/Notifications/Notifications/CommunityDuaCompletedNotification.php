<?php

namespace App\Domains\Notifications\Notifications;

use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunityDuaCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly CommunityDua $communityDua,
        private readonly User $completedBy,
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
        $completedByName = trim((string) $this->completedBy->first_name) ?: 'Someone';
        $duaAuthor = trim((string) $this->communityDua->first_name) ?: 'there';

        $view = $this->communityDua->type === CommunityDuaType::Paid
            ? 'mail.community-dua-completed-paid'
            : 'mail.community-dua-completed-free';

        return (new MailMessage)
            ->subject('Your community Dua request has had a completion.')
            ->view($view, [
                'duaAuthor' => $duaAuthor,
                'completedBy' => $completedByName,
            ]);
    }
}
