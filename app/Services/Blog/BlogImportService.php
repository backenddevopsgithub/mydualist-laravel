<?php

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\SeoMetadata;
use App\Services\Blog\Import\BlogImportSource;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogImportService extends Service
{
    public function __construct(
        private readonly BlogContentNormalizer $contentNormalizer,
        private readonly BlogImageMigrator $imageMigrator,
    ) {}

    public function import(BlogImportSource $source, bool $dryRun = false): BlogImportReport
    {
        $report = new BlogImportReport;

        foreach ($source->records() as $record) {
            try {
                $this->importRecord($record, $report, $dryRun);
            } catch (\Throwable $exception) {
                $report->addFailed($record, $exception->getMessage());
            }
        }

        return $report;
    }

    private function importRecord(WordPressPostRecord $record, BlogImportReport $report, bool $dryRun): void
    {
        $normalized = $this->contentNormalizer->normalize($record->content);
        $report->addBrokenShortcodes($record, $normalized['broken_shortcodes']);

        $content = $dryRun
            ? $normalized['content']
            : $this->imageMigrator->migrateContent($normalized['content'], $record, $report);

        $featuredImage = $dryRun
            ? $record->featuredImageUrl
            : $this->imageMigrator->migrateFeaturedImage($record->featuredImageUrl, $record, $report);

        if ($dryRun) {
            $existing = BlogPost::query()->where('wp_post_id', $record->wpPostId)->exists();

            if ($existing) {
                $report->addUpdated($record);
            } else {
                $report->addImported($record);
            }

            return;
        }

        DB::transaction(function () use ($record, $content, $featuredImage, $report): void {
            $category = $this->resolveCategory($record);
            $existing = BlogPost::query()->where('wp_post_id', $record->wpPostId)->first();

            $attributes = [
                'blog_category_id' => $category->id,
                'title' => $record->title,
                'slug' => $record->slug,
                'excerpt' => $record->excerpt,
                'content' => $content,
                'featured_image' => $featuredImage,
                'read_time_minutes' => $this->estimateReadTime($content),
                'is_published' => true,
                'published_at' => $record->publishedAt ?? now(),
            ];

            BlogPost::query()->updateOrCreate(
                ['wp_post_id' => $record->wpPostId],
                $attributes,
            );

            $this->upsertSeoMetadata($record, $featuredImage);

            if ($existing === null) {
                $report->addImported($record);
            } else {
                $report->addUpdated($record);
            }
        });
    }

    private function resolveCategory(WordPressPostRecord $record): BlogCategory
    {
        if ($record->categorySlug !== null) {
            return BlogCategory::query()->updateOrCreate(
                ['slug' => $record->categorySlug],
                [
                    'name' => $record->categoryName ?? Str::title(str_replace('-', ' ', $record->categorySlug)),
                    'sort_order' => 99,
                ],
            );
        }

        $defaultSlug = (string) config('mydualist.blog.import.default_category_slug', 'essentials');

        return BlogCategory::query()->firstOrCreate(
            ['slug' => $defaultSlug],
            [
                'name' => Str::title(str_replace('-', ' ', $defaultSlug)),
                'sort_order' => 99,
            ],
        );
    }

    private function upsertSeoMetadata(WordPressPostRecord $record, ?string $featuredImagePath): void
    {
        if ($record->metaTitle === null && $record->metaDescription === null && $record->ogImageUrl === null) {
            return;
        }

        SeoMetadata::query()->updateOrCreate(
            [
                'scope' => 'blog',
                'key' => $record->slug,
            ],
            [
                'route_name' => 'blogs.show',
                'meta_title' => $record->metaTitle,
                'meta_description' => $record->metaDescription,
                'og_title' => $record->metaTitle,
                'og_description' => $record->metaDescription,
                'og_image_path' => $featuredImagePath,
            ],
        );
    }

    private function estimateReadTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));

        return max(1, (int) ceil($words / 200));
    }
}
