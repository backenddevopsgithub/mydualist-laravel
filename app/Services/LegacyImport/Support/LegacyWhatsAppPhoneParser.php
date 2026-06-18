<?php

namespace App\Services\LegacyImport\Support;

class LegacyWhatsAppPhoneParser
{
    /**
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}
     */
    public static function parse(?string $rawPhone): array
    {
        if ($rawPhone === null) {
            return self::empty();
        }

        $rawPhone = trim($rawPhone);

        if ($rawPhone === '' || strtolower($rawPhone) === 'null') {
            return self::empty();
        }

        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        if ($digits === '') {
            return self::empty();
        }

        if (str_starts_with($rawPhone, '+') || strlen($digits) > 10) {
            return self::fromE164Digits($digits);
        }

        return [
            'whatsapp_country_code' => null,
            'whatsapp_phone' => $digits,
            'is_valid' => strlen($digits) >= 8,
        ];
    }

    /**
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}
     */
    private static function fromE164Digits(string $digits): array
    {
        $candidates = [
            ['1', 1],
            ['44', 2],
            ['61', 2],
            ['971', 3],
            ['966', 3],
            ['92', 2],
            ['20', 2],
            ['33', 2],
            ['49', 2],
        ];

        foreach ($candidates as [$code, $codeLength]) {
            if (! str_starts_with($digits, $code)) {
                continue;
            }

            $national = substr($digits, $codeLength);

            if ($national === '' || strlen($national) < 6) {
                continue;
            }

            return [
                'whatsapp_country_code' => '+'.$code,
                'whatsapp_phone' => ltrim($national, '0'),
                'is_valid' => true,
            ];
        }

        if (strlen($digits) >= 10) {
            $code = substr($digits, 0, 3);
            $national = substr($digits, 3);

            return [
                'whatsapp_country_code' => '+'.$code,
                'whatsapp_phone' => ltrim($national, '0'),
                'is_valid' => strlen($national) >= 6,
            ];
        }

        return self::empty();
    }

    /**
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}
     */
    private static function empty(): array
    {
        return [
            'whatsapp_country_code' => null,
            'whatsapp_phone' => null,
            'is_valid' => false,
        ];
    }
}
