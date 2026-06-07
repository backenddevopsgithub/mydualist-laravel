<?php

namespace App\Support;

class SupportTicketReasons
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'general' => 'General Feedback and Connect',
            'bug' => 'Reporting a Bug',
            'upgrade' => 'Upgrade Not Working',
            'page' => 'Page Not Loading',
            'account' => 'Account Settings',
            'other' => 'Other',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
