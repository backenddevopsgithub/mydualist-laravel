<?php

namespace App\Support;

class WhatsAppPhone
{
    public static function normalize(string $countryCode, string $phone): string
    {
        $countryDigits = preg_replace('/\D+/', '', $countryCode) ?? '';
        $nationalDigits = preg_replace('/\D+/', '', $phone) ?? '';
        $nationalDigits = ltrim($nationalDigits, '0');

        return '+'.$countryDigits.$nationalDigits;
    }

    public static function isValid(string $countryCode, string $phone): bool
    {
        $normalized = self::normalize($countryCode, $phone);

        return (bool) preg_match('/^\+\d{8,15}$/', $normalized);
    }

    public static function twilioAddress(string $normalizedPhone): string
    {
        return str_starts_with($normalizedPhone, 'whatsapp:')
            ? $normalizedPhone
            : 'whatsapp:'.$normalizedPhone;
    }
}
