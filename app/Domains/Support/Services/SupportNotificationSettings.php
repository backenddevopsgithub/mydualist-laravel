<?php

namespace App\Domains\Support\Services;

use App\Models\AppSetting;
use App\Services\Service;

class SupportNotificationSettings extends Service
{
    public const KEY = 'support_notification_recipients';

    /**
     * @return list<string>
     */
    public function defaultRecipients(): array
    {
        return ['arsalan@thepilgrim.co'];
    }

    /**
     * @return list<string>
     */
    public function recipients(): array
    {
        $stored = AppSetting::getValue(self::KEY);
        $recipients = is_array($stored) ? $stored : $this->defaultRecipients();

        return $this->normalizeRecipients($recipients);
    }

    /**
     * @param  list<string>  $recipients
     */
    public function saveRecipients(array $recipients): void
    {
        AppSetting::putValue(self::KEY, $this->normalizeRecipients($recipients));
    }

    /**
     * @param  list<string|array{email?: string}>  $recipients
     * @return list<string>
     */
    public function normalizeRecipients(array $recipients): array
    {
        return collect($recipients)
            ->map(function (mixed $recipient): ?string {
                if (is_array($recipient)) {
                    $recipient = $recipient['email'] ?? null;
                }

                if (! is_string($recipient)) {
                    return null;
                }

                $recipient = strtolower(trim($recipient));

                if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return $recipient;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
