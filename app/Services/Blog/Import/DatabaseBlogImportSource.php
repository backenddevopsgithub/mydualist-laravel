<?php

namespace App\Services\Blog\Import;

use App\Services\Blog\WordPressPostRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseBlogImportSource implements BlogImportSource
{
    public function records(): iterable
    {
        $connection = $this->connection();
        $prefix = $this->tablePrefix();

        $posts = $connection->table("{$prefix}posts")
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
            $meta = $this->postMeta($connection, $prefix, $postId);
            $category = $this->primaryCategory($connection, $prefix, $postId);
            $featuredImageUrl = $this->featuredImageUrl($connection, $prefix, $meta);

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

    private function connection(): \Illuminate\Database\Connection
    {
        if (blank(env('WP_DB_DATABASE'))) {
            throw new RuntimeException('WordPress database is not configured. Set WP_DB_* environment variables.');
        }

        return DB::connection('wordpress');
    }

    private function tablePrefix(): string
    {
        return (string) config('database.connections.wordpress.prefix', 'wp_');
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
     * @return array{name: string, slug: string}|null
     */
    private function primaryCategory(\Illuminate\Database\Connection $connection, string $prefix, int $postId): ?array
    {
        $term = $connection->table("{$prefix}term_relationships as tr")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join("{$prefix}terms as t", 't.term_id', '=', 'tt.term_id')
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
    private function featuredImageUrl(\Illuminate\Database\Connection $connection, string $prefix, array $meta): ?string
    {
        $thumbnailId = (int) ($meta['_thumbnail_id'] ?? 0);

        if ($thumbnailId <= 0) {
            return null;
        }

        $guid = $connection->table("{$prefix}posts")
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
