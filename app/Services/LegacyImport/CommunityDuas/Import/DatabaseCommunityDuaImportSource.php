<?php

namespace App\Services\LegacyImport\CommunityDuas\Import;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityDuaRecord;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityQueueRecord;
use App\Services\LegacyImport\Support\WordPressSerializedData;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;

class DatabaseCommunityDuaImportSource implements CommunityDuaImportSource
{
    public function duaRecords(): iterable
    {
        $connection = WordPressConnection::connection();

        $posts = $connection->table('posts')
            ->where('post_type', 'community_dua')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get(['ID', 'post_content', 'post_date']);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $meta = $this->postMeta($connection, $postId);

            yield $postId => $this->mapDua($postId, (string) $post->post_content, $post->post_date, $meta);
        }
    }

    public function queueRecords(): iterable
    {
        $connection = WordPressConnection::connection();

        $userIds = $connection->table('usermeta')
            ->whereIn('meta_key', ['_seeing_now', '_showing', '_pattern', '_completed_community_duas', '_seen_duas'])
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $meta = $this->userMeta($connection, $userId);

            if ($meta === []) {
                continue;
            }

            $seeingNow = $meta['_seeing_now'] ?? null;
            $seeingNowId = ($seeingNow !== null && $seeingNow !== '' && strtolower((string) $seeingNow) !== 'null')
                ? (int) $seeingNow
                : null;

            yield $userId => new WordPressCommunityQueueRecord(
                userWpLegacyId: $userId,
                showingType: (string) ($meta['_showing'] ?? 'paid'),
                pattern: (int) ($meta['_pattern'] ?? 0),
                seeingNowWpPostId: $seeingNowId > 0 ? $seeingNowId : null,
                completedWpPostIds: WordPressSerializedData::intList($meta['_completed_community_duas'] ?? null),
                seenWpPostIds: WordPressSerializedData::intList($meta['_seen_duas'] ?? null),
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(Connection $connection, int $postId): array
    {
        return $connection->table('postmeta')
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function userMeta(Connection $connection, int $userId): array
    {
        return $connection->table('usermeta')
            ->where('user_id', $userId)
            ->whereIn('meta_key', ['_seeing_now', '_showing', '_pattern', '_completed_community_duas', '_seen_duas'])
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
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
