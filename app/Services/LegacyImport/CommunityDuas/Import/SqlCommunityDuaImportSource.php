<?php

namespace App\Services\LegacyImport\CommunityDuas\Import;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityDuaRecord;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityQueueRecord;
use App\Services\LegacyImport\Support\WordPressSerializedData;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\SqlDumpReader;

class SqlCommunityDuaImportSource implements CommunityDuaImportSource
{
    private SqlDumpReader $reader;

    public function __construct(string $path, string $tablePrefix = 'wp_')
    {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function duaRecords(): iterable
    {
        foreach ($this->reader->postsById() as $postId => $post) {
            if (($post['post_type'] ?? '') !== 'community_dua' || ($post['post_status'] ?? '') !== 'publish') {
                continue;
            }

            $postId = (int) $postId;
            $meta = $this->reader->postmetaByPostId()[$postId] ?? [];

            yield $postId => $this->mapDua($postId, (string) ($post['post_content'] ?? ''), $post['post_date'] ?? null, $meta);
        }
    }

    public function queueRecords(): iterable
    {
        foreach ($this->reader->usermetaByUserId() as $userId => $meta) {
            $relevant = array_intersect_key($meta, array_flip([
                '_seeing_now', '_showing', '_pattern', '_completed_community_duas', '_seen_duas',
            ]));

            if ($relevant === []) {
                continue;
            }

            $seeingNow = $meta['_seeing_now'] ?? null;
            $seeingNowId = ($seeingNow !== null && $seeingNow !== '' && strtolower((string) $seeingNow) !== 'null')
                ? (int) $seeingNow
                : null;

            yield (int) $userId => new WordPressCommunityQueueRecord(
                userWpLegacyId: (int) $userId,
                showingType: (string) ($meta['_showing'] ?? 'paid'),
                pattern: (int) ($meta['_pattern'] ?? 0),
                seeingNowWpPostId: $seeingNowId > 0 ? $seeingNowId : null,
                completedWpPostIds: WordPressSerializedData::intList($meta['_completed_community_duas'] ?? null),
                seenWpPostIds: WordPressSerializedData::intList($meta['_seen_duas'] ?? null),
            );
        }
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function mapDua(int $postId, string $content, mixed $postDate, array $meta): WordPressCommunityDuaRecord
    {
        $type = strtolower((string) ($meta['_dua_type'] ?? 'free')) === 'paid'
            ? CommunityDuaType::Paid
            : CommunityDuaType::Free;

        $required = (int) ($meta['_required_completion'] ?? $type->requiredCompletions());
        $completed = (int) ($meta['_completed'] ?? 0);
        $wpOrderId = (int) ($meta['_order_id'] ?? 0) ?: null;

        $status = match (true) {
            $completed >= $required && $required > 0 => CommunityDuaStatus::Completed,
            $type === CommunityDuaType::Paid => CommunityDuaStatus::PendingPayment,
            default => CommunityDuaStatus::Active,
        };

        return new WordPressCommunityDuaRecord(
            wpPostId: $postId,
            firstName: WordPressValueMapper::nullableString($meta['_first_name'] ?? null) ?? 'Community',
            lastName: WordPressValueMapper::nullableString($meta['_last_name'] ?? null) ?? 'Member',
            email: WordPressValueMapper::nullableString($meta['_email'] ?? null) ?? "community-{$postId}@import.local",
            gender: WordPressValueMapper::normalizeGender($meta['_gender'] ?? null) ?? 'unspecified',
            content: $content,
            type: $type,
            status: $status,
            requiredCompletions: $required > 0 ? $required : $type->requiredCompletions(),
            completionCount: max(0, $completed),
            isVisible: $status === CommunityDuaStatus::Active,
            wpOrderId: $wpOrderId,
            createdAt: WordPressValueMapper::parseDateTime($postDate),
        );
    }
}
