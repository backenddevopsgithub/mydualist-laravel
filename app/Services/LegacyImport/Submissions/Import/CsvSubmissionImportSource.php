<?php

namespace App\Services\LegacyImport\Submissions\Import;

use App\Services\LegacyImport\Submissions\WordPressSubmissionRecord;
use App\Services\LegacyImport\Support\WordPressSubmissionStatusMapper;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use RuntimeException;

class CsvSubmissionImportSource implements SubmissionImportSource
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function records(): iterable
    {
        if (! is_readable($this->path)) {
            throw new RuntimeException("CSV import file is not readable: {$this->path}");
        }

        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV import file: {$this->path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return;
            }

            $headers = array_map(fn (string $header): string => strtolower(trim($header)), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                /** @var array<string, string|null> $data */
                $data = array_combine($headers, array_pad($row, count($headers), null));

                if ($data === false) {
                    continue;
                }

                $record = $this->mapRow($data);

                if ($record !== null) {
                    yield ($record->wpPostId ?? spl_object_id($record)) => $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapRow(array $data): ?WordPressSubmissionRecord
    {
        $wpPostId = (int) ($data['wp_post_id'] ?? $data['id'] ?? 0);
        $listWpPostId = (int) ($data['list_wp_post_id'] ?? $data['list_id'] ?? 0);

        if ($wpPostId === 0 || $listWpPostId <= 0) {
            return null;
        }

        $firstName = WordPressValueMapper::nullableString($data['first_name'] ?? $data['_first_name'] ?? null);

        return new WordPressSubmissionRecord(
            wpPostId: $wpPostId,
            listWpPostId: $listWpPostId,
            firstName: $firstName,
            lastName: WordPressValueMapper::nullableString($data['last_name'] ?? $data['_last_name'] ?? null),
            email: WordPressValueMapper::nullableString($data['email'] ?? $data['_email'] ?? null),
            gender: WordPressValueMapper::normalizeGender($data['gender'] ?? $data['_gender'] ?? null),
            content: (string) ($data['content'] ?? $data['post_content'] ?? ''),
            isPersonalDua: ($data['type'] ?? $data['_type'] ?? '') === 'personal' || $firstName === 'Personal Dua',
            isLocked: ! WordPressSubmissionStatusMapper::isTruthy($data['show'] ?? $data['_show'] ?? 1),
            unlockWpOrderId: (int) ($data['unlock_wp_order_id'] ?? $data['_order_id'] ?? 0) ?: null,
            reported: $data['reported'] ?? $data['_reported'] ?? false,
            visibility: (string) ($data['visibility'] ?? $data['_visibility'] ?? 'visible'),
            status: $data['status'] ?? $data['_status'] ?? 0,
            completedAt: $data['completed_at'] ?? $data['_completed_at'] ?? null,
            rawPhone: WordPressValueMapper::legacyPhone($data['phone'] ?? $data['_phone'] ?? null),
            createdAt: WordPressValueMapper::parseDateTime($data['created_at'] ?? $data['post_date'] ?? null),
            fromLegacyArray: WordPressSubmissionStatusMapper::isTruthy($data['from_legacy_array'] ?? false),
        );
    }
}
