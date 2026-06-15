<?php

namespace App\Support;

class SubmissionGenders
{
    public const MALE = 'male';

    public const FEMALE = 'female';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::MALE,
            self::FEMALE,
        ];
    }

    public static function normalize(mixed $gender): ?string
    {
        if (! is_string($gender)) {
            return null;
        }

        $normalized = strtolower(trim($gender));

        return in_array($normalized, self::values(), true) ? $normalized : null;
    }

    public static function label(?string $gender): ?string
    {
        return match (self::normalize($gender)) {
            self::MALE => 'Men',
            self::FEMALE => 'Women',
            default => null,
        };
    }
}
