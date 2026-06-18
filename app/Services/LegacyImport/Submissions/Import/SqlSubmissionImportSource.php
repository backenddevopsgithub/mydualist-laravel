<?php

namespace App\Services\LegacyImport\Submissions\Import;

use App\Services\LegacyImport\Submissions\LegacySubmissionArrayMigrator;
use App\Services\LegacyImport\Submissions\WordPressSubmissionRecord;
use App\Services\LegacyImport\Support\WordPressSubmissionStatusMapper;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\SqlDumpReader;

class SqlSubmissionImportSource implements SubmissionImportSource
{
    public function __construct(
        private readonly string $path,
        private readonly string $tablePrefix = 'wp_',
        private readonly LegacySubmissionArrayMigrator $legacyMigrator = new LegacySubmissionArrayMigrator,
    ) {}

    public function records(): iterable
    {
        $reader = new SqlDumpReader($this->path, $this->tablePrefix);

        foreach ($reader->postsById() as $listId => $listPost) {
            if (($listPost['post_type'] ?? '') !== 'dua_list') {
                continue;
            }

            $listId = (int) $listId;
            $listMeta = $reader->postmetaByPostId()[$listId] ?? [];
            $migrated = WordPressSubmissionStatusMapper::isTruthy($listMeta['migrated'] ?? false);

            foreach ($this->legacyMigrator->expand($listId, $listMeta['dua_submissions'] ?? null, $migrated) as $record) {
                yield $record->wpPostId ?? spl_object_id($record) => $record;
            }
        }

        foreach ($reader->postsById() as $postId => $post) {
            if (($post['post_type'] ?? '') !== 'submission' || ($post['post_status'] ?? '') !== 'publish') {
                continue;
            }

            $postId = (int) $postId;
            $meta = $reader->postmetaByPostId()[$postId] ?? [];
            $listWpPostId = (int) ($meta['_list_id'] ?? 0);

            if ($listWpPostId <= 0) {
                continue;
            }

            yield $postId => $this->mapSubmission(
                $postId,
                $listWpPostId,
                (string) ($post['post_content'] ?? ''),
                $post['post_date'] ?? null,
                $meta,
            );
        }
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function mapSubmission(
        int $postId,
        int $listWpPostId,
        string $content,
        mixed $postDate,
        array $meta,
    ): WordPressSubmissionRecord {
        $firstName = WordPressValueMapper::nullableString($meta['_first_name'] ?? null);

        return new WordPressSubmissionRecord(
            wpPostId: $postId,
            listWpPostId: $listWpPostId,
            firstName: $firstName,
            lastName: WordPressValueMapper::nullableString($meta['_last_name'] ?? null),
            email: WordPressValueMapper::nullableString($meta['_email'] ?? null),
            gender: WordPressValueMapper::normalizeGender($meta['_gender'] ?? null),
            content: $content,
            isPersonalDua: ($meta['_type'] ?? '') === 'personal' || $firstName === 'Personal Dua',
            isLocked: ! WordPressSubmissionStatusMapper::isTruthy($meta['_show'] ?? 1),
            unlockWpOrderId: (int) ($meta['_order_id'] ?? 0) ?: null,
            reported: $meta['_reported'] ?? false,
            visibility: $meta['_visibility'] ?? 'visible',
            status: $meta['_status'] ?? 0,
            completedAt: $meta['_completed_at'] ?? null,
            rawPhone: WordPressValueMapper::nullableString($meta['_phone'] ?? null),
            createdAt: WordPressValueMapper::parseDateTime($postDate),
        );
    }
}
