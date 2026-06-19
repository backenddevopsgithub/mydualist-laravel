<?php

namespace App\Services\LegacyImport\Support;

class LegacyWhatsAppPhoneParser
{
    public const WHATSAPP_COUNTRY_CODE_MAX_LENGTH = 6;

    public const WHATSAPP_PHONE_MAX_LENGTH = 30;

    /**
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}
     */
    public static function parse(mixed $rawPhone): array
    {
        $rawPhone = WordPressValueMapper::legacyPhone($rawPhone);

        if ($rawPhone === null) {
            return self::empty();
        }

        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        if ($digits === '') {
            return self::empty();
        }

        if (str_starts_with($rawPhone, '+') || strlen($digits) > 10) {
            $parsed = self::fromE164Digits($digits);

            if ($parsed['whatsapp_phone'] !== null || $parsed['whatsapp_country_code'] !== null) {
                return self::normalizeParsed($parsed);
            }
        } else {
            return self::normalizeParsed([
                'whatsapp_country_code' => null,
                'whatsapp_phone' => $digits,
                'is_valid' => strlen($digits) >= 8,
            ]);
        }

        return self::normalizeParsed([
            'whatsapp_country_code' => null,
            'whatsapp_phone' => $digits,
            'is_valid' => false,
        ]);
    }

    /**
     * @param  array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}  $parsed
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, is_valid: bool}
     */
    private static function normalizeParsed(array $parsed): array
    {
        return [
            'whatsapp_country_code' => self::truncate($parsed['whatsapp_country_code'], self::WHATSAPP_COUNTRY_CODE_MAX_LENGTH),
            'whatsapp_phone' => self::truncate($parsed['whatsapp_phone'], self::WHATSAPP_PHONE_MAX_LENGTH),
            'is_valid' => $parsed['is_valid'],
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

    private static function truncate(?string $value, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
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
