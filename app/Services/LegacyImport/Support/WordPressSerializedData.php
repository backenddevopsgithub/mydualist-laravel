<?php

namespace App\Services\LegacyImport\Support;

class WordPressSerializedData
{
    /**
     * @return list<int>
     */
    public static function intList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parsed = @unserialize($value, ['allowed_classes' => false]);

        if (! is_array($parsed)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): int => (int) $item,
            $parsed,
        ), static fn (int $id): bool => $id > 0)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function submissionArray(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parsed = @unserialize($value, ['allowed_classes' => false]);

        return is_array($parsed) ? array_values($parsed) : [];
    }
}
