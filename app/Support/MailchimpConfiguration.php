<?php

namespace App\Support;

class MailchimpConfiguration
{
    public static function isEnabled(): bool
    {
        return (bool) config('services.mailchimp.enabled');
    }
}
