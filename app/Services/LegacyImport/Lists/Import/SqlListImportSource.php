<?php

namespace App\Services\LegacyImport\Lists\Import;

use App\Models\DuaList;
use App\Services\LegacyImport\Lists\WordPressListRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\SqlDumpReader;

class SqlListImportSource implements ListImportSource
{
    private SqlDumpReader $reader;

    public function __construct(string $path, string $tablePrefix = 'wp_')
    {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function records(): iterable
    {
        foreach ($this->reader->postsById() as $postId => $post) {
            if (($post['post_type'] ?? '') !== 'dua_list') {
                continue;
            }

            if (! in_array($post['post_status'] ?? '', ['publish', 'trash'], true)) {
                continue;
            }

            $meta = $this->reader->postmetaByPostId()[$postId] ?? [];
            $ownerWpId = (int) ($meta['user'] ?? 0);

            if ($ownerWpId <= 0) {
                continue;
            }

            $ownerMeta = $this->reader->usermetaByUserId()[$ownerWpId] ?? [];
            $coverImageUrl = $this->resolveCoverImageUrl($meta);

            yield $postId => $this->mapPost($postId, $post, $meta, $ownerMeta, $coverImageUrl);
        }
    }

    /**
     * @param  array<string, string|null>  $post
     * @param  array<string, string>  $meta
     * @param  array<string, string>  $ownerMeta
     */
    private function mapPost(int $postId, array $post, array $meta, array $ownerMeta, ?string $coverImageUrl): WordPressListRecord
    {
        $slug = trim((string) ($post['post_name'] ?? ''));

        if ($slug === '') {
            $slug = 'list-'.$postId;
        }

        $isActive = ($meta['status'] ?? '1') === '1';
        $postDate = WordPressValueMapper::parseDateTime($post['post_date'] ?? null);

        $listMode = WordPressValueMapper::nullableString($meta['listMode'] ?? null);

        return new WordPressListRecord(
            wpPostId: $postId,
            ownerWpLegacyId: (int) $meta['user'],
            title: (string) ($post['post_title'] ?? 'Untitled List'),
            slug: $slug,
            occasion: WordPressValueMapper::normalizeOccasion($meta['category'] ?? null),
            startDate: WordPressValueMapper::parseDate($meta['tripStart'] ?? null),
            endDate: WordPressValueMapper::parseDate($meta['tripEnd'] ?? null),
            coverImageUrl: $coverImageUrl,
            status: $isActive ? DuaList::STATUS_ACTIVE : DuaList::STATUS_ARCHIVED,
            publishedAt: $isActive ? ($postDate ?? now()) : null,
            isTrashed: ($post['post_status'] ?? '') === 'trash',
            ownerPreferences: WordPressValueMapper::ownerListPreferences($ownerMeta),
            listMode: $listMode === 'creator' ? 'creator' : null,
            donationLink: WordPressValueMapper::nullableString($meta['donationLink'] ?? null),
            donationNote: WordPressValueMapper::nullableString($meta['donationNote'] ?? null),
            insightsViews: (int) ($meta['_insights_views'] ?? 0),
            insightsClicks: (int) ($meta['_insights_clicks'] ?? 0),
            createdAt: $postDate,
            updatedAt: WordPressValueMapper::parseDateTime($post['post_modified'] ?? null),
        );
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function resolveCoverImageUrl(array $meta): ?string
    {
        $attachmentId = (int) ($meta['listImage'] ?? 0);

        if ($attachmentId <= 0) {
            return null;
        }

        $attachment = $this->reader->postsById()[$attachmentId] ?? null;

        return WordPressValueMapper::nullableString($attachment['guid'] ?? null);
    }
}
