<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\SeoMetadata;
use App\Services\Blog\BlogContentNormalizer;
use App\Services\Blog\BlogImageMigrator;
use App\Services\Blog\BlogImportReport;
use App\Services\Blog\BlogImportService;
use App\Services\Blog\WordPressPostRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    BlogCategory::query()->create([
        'name' => 'Essentials',
        'slug' => 'essentials',
        'sort_order' => 5,
    ]);
});

test('blog import transforms shortcodes during import', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $csvPath = base_path('tests/Fixtures/blog-import/sample.csv');
    $reportPath = storage_path('app/testing-blog-import-report.json');

    Artisan::call('blog:import', [
        '--csv' => $csvPath,
        '--report' => $reportPath,
    ]);

    $post = BlogPost::query()->where('wp_post_id', 101)->first();

    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('Test Import Post')
        ->and($post->slug)->toBe('test-import-post')
        ->and($post->content)->toContain('post-info-box alert')
        ->and($post->content)->not->toContain('[warning_box]')
        ->and($post->content)->not->toContain('<p>&nbsp;</p>')
        ->and($post->excerpt)->toBe('Short excerpt');

    $seo = SeoMetadata::query()->where('scope', 'blog')->where('key', 'test-import-post')->first();

    expect($seo)->not->toBeNull()
        ->and($seo->meta_title)->toBe('SEO Title')
        ->and($seo->meta_description)->toBe('SEO Description');
});

test('blog import rewrites image urls and downloads assets', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $csvPath = base_path('tests/Fixtures/blog-import/sample.csv');

    Artisan::call('blog:import', ['--csv' => $csvPath]);

    $post = BlogPost::query()->where('wp_post_id', 101)->firstOrFail();

    expect($post->content)->not->toContain('https://thepilgrim.co/wp-content/uploads/')
        ->and($post->content)->toContain('/storage/blog-images/')
        ->and($post->featured_image)->toStartWith('blog-images/');

    Storage::disk('public')->assertExists($post->featured_image);
});

test('blog import is idempotent on repeated runs', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $csvPath = base_path('tests/Fixtures/blog-import/sample.csv');

    Artisan::call('blog:import', ['--csv' => $csvPath]);
    Artisan::call('blog:import', ['--csv' => $csvPath]);

    expect(BlogPost::query()->where('wp_post_id', 101)->count())->toBe(1);

    $report = json_decode(file_get_contents(storage_path('app/blog-import-report.json')), true);

    expect($report['counts']['imported'])->toBe(0)
        ->and($report['counts']['updated'])->toBe(1);
});

test('blog content normalizer removes ez-toc markup and empty paragraphs', function () {
    $normalizer = app(BlogContentNormalizer::class);

    $content = <<<'HTML'
<div id="ez-toc-container" class="ez-toc-v2_0_48 counter-hierarchy ez-toc-counter ez-toc-grey ez-toc-container-direction">
<div class="ez-toc-title-container"><p class="ez-toc-title">Table of Contents</p></div>
<nav><ul class="ez-toc-list ez-toc-list-level-1"><li><a href="#section">Section</a></li></ul></nav>
</div>
<p>Intro</p>
<p>&nbsp;</p>
HTML;

    $result = $normalizer->normalize($content);

    expect($result['content'])->not->toContain('ez-toc-container')
        ->and($result['content'])->not->toContain('&nbsp;')
        ->and($result['content'])->toContain('<p>Intro</p>');
});

test('blog image migrator records missing images', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('', 404),
    ]);

    $record = new WordPressPostRecord(
        wpPostId: 55,
        title: 'Missing Image Post',
        slug: 'missing-image-post',
        content: '<p><img src="https://thepilgrim.co/wp-content/uploads/2024/01/missing.jpg" /></p>',
    );

    $report = new BlogImportReport;
    $migrator = app(BlogImageMigrator::class);
    $migrator->migrateContent($record->content, $record, $report);

    expect($report->missingImages)->toHaveCount(1)
        ->and($report->missingImages[0]['url'])->toContain('missing.jpg');
});

test('blog import service reports broken shortcodes that survive transformation', function () {
    Http::fake();

    $source = new class implements \App\Services\Blog\Import\BlogImportSource
    {
        public function records(): iterable
        {
            yield 202 => new WordPressPostRecord(
                wpPostId: 202,
                title: 'Broken Shortcode Post',
                slug: 'broken-shortcode-post',
                content: '[popup_cta title="Join"][/popup_cta]',
            );
        }
    };

    $report = app(BlogImportService::class)->import($source);

    expect($report->brokenShortcodes)->toHaveCount(1)
        ->and($report->brokenShortcodes[0]['shortcodes'])->toContain('popup_cta');
});

test('sql dump import source parses published posts', function () {
    $sql = <<<'SQL'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(500, 1, '2024-02-01 09:00:00', '2024-02-01 09:00:00', '<p>SQL imported body</p>', 'SQL Import Post', 'SQL excerpt', 'publish', 'open', 'open', '', 'sql-import-post', '', '', '2024-02-01 09:00:00', '2024-02-01 09:00:00', '', 0, 'https://example.test/?p=500', 0, 'post', '', 0);
SQL;

    $path = storage_path('app/testing-blog-import.sql');
    file_put_contents($path, $sql);

    Http::fake();

    Artisan::call('blog:import', ['--sql' => $path]);

    $post = BlogPost::query()->where('wp_post_id', 500)->first();

    expect($post)->not->toBeNull()
        ->and($post->slug)->toBe('sql-import-post')
        ->and($post->content)->toContain('SQL imported body');
});
