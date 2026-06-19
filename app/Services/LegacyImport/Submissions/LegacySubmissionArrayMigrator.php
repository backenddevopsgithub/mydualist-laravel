<?php

namespace App\Services\LegacyImport\Submissions;

use App\Services\LegacyImport\Support\WordPressSerializedData;
use App\Services\LegacyImport\Support\WordPressSubmissionStatusMapper;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use Illuminate\Support\Collection;

class LegacySubmissionArrayMigrator
{
    /**
     * @return Collection<int, WordPressSubmissionRecord>
     */
    public function expand(int $listWpPostId, ?string $legacyMeta, bool $migrated): Collection
    {
        if ($migrated || $legacyMeta === null || trim($legacyMeta) === '') {
            return collect();
        }

        $rows = WordPressSerializedData::submissionArray($legacyMeta);

        if ($rows === []) {
            return collect();
        }

        return collect($rows)->values()->map(function (array $submission, int $index) use ($listWpPostId): WordPressSubmissionRecord {
            $firstName = (string) ($submission['first_name'] ?? '');
            $isPersonal = $firstName === 'Personal Dua';

            return new WordPressSubmissionRecord(
                wpPostId: -1 * (($listWpPostId * 10000) + ($index + 1)),
                listWpPostId: $listWpPostId,
                firstName: $firstName,
                lastName: (string) ($submission['last_name'] ?? ''),
                email: WordPressValueMapper::nullableString($submission['email'] ?? null),
                gender: WordPressValueMapper::normalizeGender($submission['gender'] ?? null),
                content: (string) ($submission['message'] ?? $submission['content'] ?? ''),
                isPersonalDua: $isPersonal || (($submission['type'] ?? '') === 'personal'),
                isLocked: ! WordPressSubmissionStatusMapper::isTruthy($submission['show'] ?? 1),
                unlockWpOrderId: null,
                reported: false,
                visibility: 'visible',
                status: $submission['status'] ?? 0,
                completedAt: null,
                rawPhone: WordPressValueMapper::legacyPhone($submission['phone'] ?? null),
                createdAt: null,
                fromLegacyArray: true,
            );
        });
    }
}
