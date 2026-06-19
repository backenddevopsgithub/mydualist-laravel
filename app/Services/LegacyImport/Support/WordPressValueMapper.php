<?php

namespace App\Services\LegacyImport\Support;

use App\Enums\UserRole;
use App\Support\DuaListDisplayOrders;
use Carbon\Carbon;

class WordPressValueMapper
{
    public static function normalizeGender(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return match (strtolower(trim($value))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => strtolower(trim($value)),
        };
    }

    public static function normalizeOccasion(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'general';
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'umrah' => 'umrah',
            'hajj' => 'hajj',
            'ramadan' => 'ramadan',
            'salawat' => 'salawat',
            'rawdah', 'rawadah' => 'rawadah',
            'general' => 'general',
            default => str($normalized)->slug('_')->toString(),
        };
    }

    public static function normalizeDisplayOrder(?string $value): string
    {
        return match ($value) {
            'order_by_gender' => DuaListDisplayOrders::GENDER,
            'order_by_person' => DuaListDisplayOrders::PERSON,
            'order_by_date', null, '' => DuaListDisplayOrders::DATE,
            default => DuaListDisplayOrders::DEFAULT,
        };
    }

    public static function normalizeEmailFrequency(?string $value): string
    {
        return match ($value) {
            'daily' => 'daily_summary',
            'every_dua', null, '' => 'every_submission',
            default => 'every_submission',
        };
    }

    public static function normalizeDuaLimitPerPerson(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }

    public static function resolveUserRole(?string $capabilities): UserRole
    {
        if ($capabilities === null || $capabilities === '') {
            return UserRole::User;
        }

        if (preg_match('/s:13:"administrator";b:1/', $capabilities) === 1) {
            return UserRole::Admin;
        }

        if (str_contains($capabilities, 'administrator')) {
            return UserRole::Admin;
        }

        return UserRole::User;
    }

    public static function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function parseDateTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function legacyPhone(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $value = sprintf('%.0f', $value);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $meta
     * @return array{dua_limit_per_person: ?int, display_order: string, email_frequency: string}
     */
    public static function ownerListPreferences(array $meta): array
    {
        return [
            'dua_limit_per_person' => self::normalizeDuaLimitPerPerson($meta['dua_limit_per_person'] ?? null),
            'display_order' => self::normalizeDisplayOrder($meta['dua_display_order'] ?? null),
            'email_frequency' => self::normalizeEmailFrequency($meta['frequency_of_emails'] ?? null),
        ];
    }
}
