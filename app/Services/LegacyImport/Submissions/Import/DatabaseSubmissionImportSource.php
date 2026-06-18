<?php

namespace App\Services\LegacyImport\Submissions\Import;

use App\Services\LegacyImport\Submissions\LegacySubmissionArrayMigrator;
use App\Services\LegacyImport\Submissions\WordPressSubmissionRecord;
use App\Services\LegacyImport\Support\WordPressSubmissionStatusMapper;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;

class DatabaseSubmissionImportSource implements SubmissionImportSource
{
    public function __construct(
        private readonly LegacySubmissionArrayMigrator $legacyMigrator = new LegacySubmissionArrayMigrator,
    ) {}

    public function records(): iterable
    {
        $connection = WordPressConnection::connection();
        $prefix = WordPressConnection::prefix();

        $lists = $connection->table("{$prefix}posts")
            ->where('post_type', 'dua_list')
            ->orderBy('ID')
            ->pluck('ID');

        foreach ($lists as $listId) {
            $listId = (int) $listId;
            $listMeta = $this->postMeta($connection, $prefix, $listId);
            $migrated = WordPressSubmissionStatusMapper::isTruthy($listMeta['migrated'] ?? false);

            foreach ($this->legacyMigrator->expand($listId, $listMeta['dua_submissions'] ?? null, $migrated) as $record) {
                yield $record->wpPostId ?? spl_object_id($record) => $record;
            }

            $submissions = $connection->table("{$prefix}posts as p")
                ->join("{$prefix}postmeta as pm", function ($join): void {
                    $join->on('pm.post_id', '=', 'p.ID')->where('pm.meta_key', '_list_id');
                })
                ->where('p.post_type', 'submission')
                ->where('p.post_status', 'publish')
                ->where('pm.meta_value', (string) $listId)
                ->orderBy('p.ID')
                ->get(['p.ID', 'p.post_content', 'p.post_date']);

            foreach ($submissions as $submission) {
                $postId = (int) $submission->ID;
                $meta = $this->postMeta($connection, $prefix, $postId);

                yield $postId => $this->mapSubmission(
                    $postId,
                    $listId,
                    (string) $submission->post_content,
                    $submission->post_date,
                    $meta,
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(Connection $connection, string $prefix, int $postId): array
    {
        return $connection->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
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
