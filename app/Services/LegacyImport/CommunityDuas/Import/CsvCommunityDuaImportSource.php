<?php

namespace App\Services\LegacyImport\CommunityDuas\Import;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityDuaRecord;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityQueueRecord;
use App\Services\LegacyImport\Support\WordPressSerializedData;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use RuntimeException;

class CsvCommunityDuaImportSource implements CommunityDuaImportSource
{
    public function __construct(
        private readonly string $duaPath,
        private readonly ?string $queuePath = null,
    ) {}

    public function duaRecords(): iterable
    {
        foreach ($this->readCsv($this->duaPath) as $data) {
            $record = $this->mapDuaRow($data);

            if ($record !== null) {
                yield $record->wpPostId => $record;
            }
        }
    }

    public function queueRecords(): iterable
    {
        if ($this->queuePath === null || ! is_readable($this->queuePath)) {
            return;
        }

        foreach ($this->readCsv($this->queuePath) as $data) {
            $record = $this->mapQueueRow($data);

            if ($record !== null) {
                yield $record->userWpLegacyId => $record;
            }
        }
    }

    /**
     * @return iterable<array<string, string|null>>
     */
    private function readCsv(string $path): iterable
    {
        if (! is_readable($path)) {
            throw new RuntimeException("CSV import file is not readable: {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV import file: {$path}");
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

                $data = array_combine($headers, array_pad($row, count($headers), null));

                if ($data !== false) {
                    yield $data;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapDuaRow(array $data): ?WordPressCommunityDuaRecord
    {
        $wpPostId = (int) ($data['wp_post_id'] ?? $data['id'] ?? 0);

        if ($wpPostId <= 0) {
            return null;
        }

        $type = strtolower((string) ($data['type'] ?? $data['_dua_type'] ?? 'free')) === 'paid'
            ? CommunityDuaType::Paid
            : CommunityDuaType::Free;

        $required = (int) ($data['required_completions'] ?? $data['_required_completion'] ?? $type->requiredCompletions());
        $completed = (int) ($data['completion_count'] ?? $data['_completed'] ?? 0);
        $wpOrderId = (int) ($data['wp_order_id'] ?? $data['_order_id'] ?? 0) ?: null;

        $status = match (true) {
            $completed >= $required && $required > 0 => CommunityDuaStatus::Completed,
            $type === CommunityDuaType::Paid => CommunityDuaStatus::PendingPayment,
            default => CommunityDuaStatus::Active,
        };

        return new WordPressCommunityDuaRecord(
            wpPostId: $wpPostId,
            firstName: (string) ($data['first_name'] ?? $data['_first_name'] ?? 'Community'),
            lastName: (string) ($data['last_name'] ?? $data['_last_name'] ?? 'Member'),
            email: (string) ($data['email'] ?? $data['_email'] ?? "community-{$wpPostId}@import.local"),
            gender: WordPressValueMapper::normalizeGender($data['gender'] ?? $data['_gender'] ?? null) ?? 'unspecified',
            content: (string) ($data['content'] ?? $data['post_content'] ?? ''),
            type: $type,
            status: $status,
            requiredCompletions: $required > 0 ? $required : $type->requiredCompletions(),
            completionCount: max(0, $completed),
            isVisible: $status === CommunityDuaStatus::Active,
            wpOrderId: $wpOrderId,
            createdAt: WordPressValueMapper::parseDateTime($data['created_at'] ?? $data['post_date'] ?? null),
        );
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapQueueRow(array $data): ?WordPressCommunityQueueRecord
    {
        $userId = (int) ($data['user_wp_legacy_id'] ?? $data['user_id'] ?? 0);

        if ($userId <= 0) {
            return null;
        }

        $seeingNow = (int) ($data['seeing_now_wp_post_id'] ?? $data['_seeing_now'] ?? 0) ?: null;

        return new WordPressCommunityQueueRecord(
            userWpLegacyId: $userId,
            showingType: (string) ($data['showing_type'] ?? $data['_showing'] ?? 'paid'),
            pattern: (int) ($data['pattern'] ?? $data['_pattern'] ?? 0),
            seeingNowWpPostId: $seeingNow,
            completedWpPostIds: $this->parseIdList($data['completed_wp_post_ids'] ?? $data['_completed_community_duas'] ?? null),
            seenWpPostIds: $this->parseIdList($data['seen_wp_post_ids'] ?? $data['_seen_duas'] ?? null),
        );
    }

    /**
     * @return list<int>
     */
    private function parseIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value) && preg_match('/[;,]/', $value)) {
            return array_values(array_filter(array_map('intval', preg_split('/[;,]/', $value))));
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            return [(int) trim($value)];
        }

        return WordPressSerializedData::intList(is_string($value) ? $value : null);
    }
}
