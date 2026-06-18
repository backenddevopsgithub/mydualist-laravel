<?php

namespace App\Services\LegacyImport\Support;

use App\Enums\DuaSubmissionStatus;
use Carbon\Carbon;

class WordPressSubmissionStatusMapper
{
    /**
     * @return array{status: DuaSubmissionStatus, hidden_at: ?Carbon, reported_at: ?Carbon, completed_at: ?Carbon}
     */
    public static function map(
        mixed $reported,
        ?string $visibility,
        mixed $status,
        mixed $completedAt,
    ): array {
        $isReported = self::isTruthy($reported);
        $isHidden = strtolower((string) $visibility) === 'hidden';
        $isCompleted = (string) $status === '1';

        if ($isReported) {
            return [
                'status' => DuaSubmissionStatus::Reported,
                'hidden_at' => null,
                'reported_at' => self::timestamp($completedAt) ?? now(),
                'completed_at' => self::timestamp($completedAt),
            ];
        }

        if ($isHidden) {
            return [
                'status' => DuaSubmissionStatus::Hidden,
                'hidden_at' => self::timestamp($completedAt),
                'reported_at' => null,
                'completed_at' => self::timestamp($completedAt),
            ];
        }

        if ($isCompleted) {
            return [
                'status' => DuaSubmissionStatus::Completed,
                'hidden_at' => null,
                'reported_at' => null,
                'completed_at' => self::timestamp($completedAt) ?? now(),
            ];
        }

        return [
            'status' => DuaSubmissionStatus::Pending,
            'hidden_at' => null,
            'reported_at' => null,
            'completed_at' => null,
        ];
    }

    public static function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes'], true);
    }

    public static function timestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return WordPressValueMapper::parseDateTime((string) $value);
    }
}
