<?php

use App\Domains\Cms\Services\SiteSeoSyncService;
use App\Models\CmsPage;
use App\Models\SeoMetadata;
use App\Support\CmsPageSlugs;
use App\Support\Seo\SeoPresenter;
use Database\Seeders\CmsPageSeeder;

test('site seo sync registers static and cms pages', function () {
    $this->seed(CmsPageSeeder::class);

    $created = app(SiteSeoSyncService::class)->sync();

    expect($created)->toBeGreaterThan(0);

    expect(SeoMetadata::query()->where('key', 'home')->where('scope', 'route')->exists())->toBeTrue()
        ->and(SeoMetadata::query()->where('key', CmsPageSlugs::PRIVACY_POLICY)->where('scope', 'cms')->exists())->toBeTrue();

    $secondSync = app(SiteSeoSyncService::class)->sync();

    expect($secondSync)->toBe(0);
});

test('homepage uses seo metadata from site seo records', function () {
    app(SiteSeoSyncService::class)->sync();

    SeoMetadata::query()->where('key', 'home')->update([
        'meta_title' => 'Custom Homepage SEO Title',
        'meta_description' => 'Custom homepage description for Google.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Custom Homepage SEO Title - My Dua List</title>', false)
        ->assertSee('meta name="description" content="Custom homepage description for Google."', false);
});

test('editing cms page seo syncs the site seo record', function () {
    $page = CmsPage::query()->create([
        'slug' => 'about-us',
        'title' => 'About Us',
        'section' => 'company',
        'content' => '<p>About content</p>',
        'is_published' => true,
        'published_at' => now(),
        'meta_title' => 'About MyDualist',
        'meta_description' => 'Learn about our mission.',
    ]);

    app(SiteSeoSyncService::class)->syncCmsPage($page);

    $seo = SeoMetadata::query()->where('key', 'about-us')->first();

    expect($seo?->meta_title)->toBe('About MyDualist')
        ->and($seo?->meta_description)->toBe('Learn about our mission.');
});

test('route seo presenter falls back when no database record exists', function () {
    $presenter = SeoPresenter::forRoute('home', 'Fallback title', 'Fallback description');

    expect($presenter->title)->toBe('Fallback title')
        ->and($presenter->description)->toBe('Fallback description');
});

test('site seo admin list is available to admins', function () {
    $admin = \App\Models\User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/seo-metadatas')
        ->assertOk();
});
