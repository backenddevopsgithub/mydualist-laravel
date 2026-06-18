<?php

namespace App\Services\LegacyImport\Lists\Import;

use App\Models\DuaList;
use App\Services\LegacyImport\Lists\WordPressListRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\WordPressConnection;

class DatabaseListImportSource implements ListImportSource
{
    public function records(): iterable
    {
        $connection = WordPressConnection::connection();
        $prefix = WordPressConnection::prefix();

        $posts = $connection->table("{$prefix}posts")
            ->where('post_type', 'dua_list')
            ->whereIn('post_status', ['publish', 'trash'])
            ->orderBy('ID')
            ->get([
                'ID',
                'post_title',
                'post_name',
                'post_date',
                'post_modified',
                'post_status',
            ]);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $meta = $this->postMeta($connection, $prefix, $postId);
            $ownerWpId = (int) ($meta['user'] ?? 0);

            if ($ownerWpId <= 0) {
                continue;
            }

            $ownerMeta = $this->userMeta($connection, $prefix, $ownerWpId);
            $coverImageUrl = $this->resolveCoverImageUrl($connection, $prefix, $meta);

            yield $postId => $this->mapPost($post, $meta, $ownerMeta, $coverImageUrl);
        }
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(\Illuminate\Database\Connection $connection, string $prefix, int $postId): array
    {
        return $connection->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function userMeta(\Illuminate\Database\Connection $connection, string $prefix, int $userId): array
    {
        return $connection->table("{$prefix}usermeta")
            ->where('user_id', $userId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function resolveCoverImageUrl(\Illuminate\Database\Connection $connection, string $prefix, array $meta): ?string
    {
        $attachmentId = (int) ($meta['listImage'] ?? 0);

        if ($attachmentId <= 0) {
            return null;
        }

        $guid = $connection->table("{$prefix}posts")
            ->where('ID', $attachmentId)
            ->value('guid');

        return WordPressValueMapper::nullableString($guid);
    }

    /**
     * @param  object  $post
     * @param  array<string, string>  $meta
     * @param  array<string, string>  $ownerMeta
     */
    private function mapPost(object $post, array $meta, array $ownerMeta, ?string $coverImageUrl): WordPressListRecord
    {
        $postId = (int) $post->ID;
        $slug = trim((string) $post->post_name);
        $isActive = ($meta['status'] ?? '1') === '1';
        $postDate = WordPressValueMapper::parseDateTime($post->post_date ?? null);
        $listMode = WordPressValueMapper::nullableString($meta['listMode'] ?? null);

        return new WordPressListRecord(
            wpPostId: $postId,
            ownerWpLegacyId: (int) $meta['user'],
            title: (string) $post->post_title,
            slug: $slug,
            occasion: WordPressValueMapper::normalizeOccasion($meta['category'] ?? null),
            startDate: WordPressValueMapper::parseDate($meta['tripStart'] ?? null),
            endDate: WordPressValueMapper::parseDate($meta['tripEnd'] ?? null),
            coverImageUrl: $coverImageUrl,
            status: $isActive ? DuaList::STATUS_ACTIVE : DuaList::STATUS_ARCHIVED,
            publishedAt: $isActive ? ($postDate ?? now()) : null,
            isTrashed: ($post->post_status ?? '') === 'trash',
            ownerPreferences: WordPressValueMapper::ownerListPreferences($ownerMeta),
            listMode: $listMode === 'creator' ? 'creator' : null,
            donationLink: WordPressValueMapper::nullableString($meta['donationLink'] ?? null),
            donationNote: WordPressValueMapper::nullableString($meta['donationNote'] ?? null),
            insightsViews: (int) ($meta['_insights_views'] ?? 0),
            insightsClicks: (int) ($meta['_insights_clicks'] ?? 0),
            createdAt: $postDate,
            updatedAt: WordPressValueMapper::parseDateTime($post->post_modified ?? null),
        );
    }
}
