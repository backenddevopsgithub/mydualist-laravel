<?php

namespace App\Services\Blog\Import;

use App\Services\Blog\WordPressPostRecord;
use App\Support\WordPress\WordPressConnection;
use Carbon\Carbon;
use Illuminate\Database\Connection;

class DatabaseBlogImportSource implements BlogImportSource
{
    public function records(): iterable
    {
        $connection = WordPressConnection::connection();

        $posts = $connection->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get([
                'ID',
                'post_title',
                'post_name',
                'post_excerpt',
                'post_content',
                'post_date',
            ]);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $meta = $this->postMeta($connection, $postId);
            $category = $this->primaryCategory($connection, $postId);
            $featuredImageUrl = $this->featuredImageUrl($connection, $meta);

            yield $postId => new WordPressPostRecord(
                wpPostId: $postId,
                title: (string) $post->post_title,
                slug: (string) $post->post_name,
                content: (string) $post->post_content,
                excerpt: $this->nullableString($post->post_excerpt),
                publishedAt: $this->parseDate($post->post_date),
                featuredImageUrl: $featuredImageUrl,
                categorySlug: $category['slug'] ?? null,
                categoryName: $category['name'] ?? null,
                metaTitle: $this->nullableString($meta['_yoast_wpseo_title'] ?? null),
                metaDescription: $this->nullableString($meta['_yoast_wpseo_metadesc'] ?? null),
                ogImageUrl: $this->nullableString($meta['_yoast_wpseo_opengraph-image'] ?? null),
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
     * @return array{name: string, slug: string}|null
     */
    private function primaryCategory(Connection $connection, int $postId): ?array
    {
        $term = $connection->table('term_relationships as tr')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->orderBy('tt.term_id')
            ->select(['t.name', 't.slug'])
            ->first();

        if ($term === null) {
            return null;
        }

        return [
            'name' => (string) $term->name,
            'slug' => (string) $term->slug,
        ];
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function featuredImageUrl(Connection $connection, array $meta): ?string
    {
        $thumbnailId = (int) ($meta['_thumbnail_id'] ?? 0);

        if ($thumbnailId <= 0) {
            return null;
        }

        $guid = $connection->table('posts')
            ->where('ID', $thumbnailId)
            ->value('guid');

        return $this->nullableString($guid);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
