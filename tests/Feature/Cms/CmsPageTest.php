<?php

use App\Models\CmsPage;
use App\Support\CmsPageSlugs;
use App\Support\Seo\SeoPresenter;
use Database\Seeders\CmsPageSeeder;

test('published cms pages render successfully', function () {
    $this->seed(CmsPageSeeder::class);

    $this->get(route('cms.show', CmsPageSlugs::PRIVACY_POLICY))
        ->assertOk()
        ->assertSee('Privacy Policy', false)
        ->assertSee('Last updated', false);

    $this->get(route('cms.show', CmsPageSlugs::TERMS_AND_CONDITIONS))
        ->assertOk()
        ->assertSee('Terms and Conditions', false);
});

test('unpublished cms pages return 404', function () {
    CmsPage::query()->create([
        'slug' => CmsPageSlugs::PRIVACY_POLICY,
        'title' => 'Privacy Policy',
        'section' => 'legal',
        'content' => '<p>Draft privacy policy.</p>',
        'is_published' => false,
    ]);

    $this->get(route('cms.show', CmsPageSlugs::PRIVACY_POLICY))
        ->assertNotFound();
});

test('unknown cms slugs fall through to public dua list resolution or 404', function () {
    $this->get('/unknown-cms-slug-xyz')
        ->assertNotFound();
});

test('cms page slug takes precedence over dua list slug', function () {
    $this->seed(CmsPageSeeder::class);

    $duaList = \App\Models\DuaList::factory()->create([
        'slug' => CmsPageSlugs::PRIVACY_POLICY,
        'status' => \App\Models\DuaList::STATUS_ACTIVE,
        'published_at' => now(),
    ]);

    $this->get(route('cms.show', CmsPageSlugs::PRIVACY_POLICY))
        ->assertOk()
        ->assertSee('Privacy Policy', false)
        ->assertDontSee($duaList->title);
});

test('footer links render only for published cms pages', function () {
    CmsPage::query()->create([
        'slug' => CmsPageSlugs::PRIVACY_POLICY,
        'title' => 'Privacy Policy',
        'section' => 'legal',
        'content' => '<p>Published privacy policy.</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    CmsPage::query()->create([
        'slug' => CmsPageSlugs::TERMS_AND_CONDITIONS,
        'title' => 'Terms and Conditions',
        'section' => 'legal',
        'content' => '<p>Draft terms.</p>',
        'is_published' => false,
    ]);

    CmsPage::query()->create([
        'slug' => CmsPageSlugs::HELP_AND_SUPPORT,
        'title' => 'Help and Support',
        'section' => 'support',
        'content' => '<p>Help content.</p>',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Privacy Policy', false)
        ->assertSee('Help and Support', false)
        ->assertDontSee('Terms and Conditions');
});

test('cms pages render seo metadata from page and seo records', function () {
    $page = CmsPage::query()->create([
        'slug' => CmsPageSlugs::PRIVACY_POLICY,
        'title' => 'Privacy Policy',
        'section' => 'legal',
        'content' => '<p>Published privacy policy.</p>',
        'is_published' => true,
        'published_at' => now(),
        'meta_title' => 'Privacy Policy Meta',
        'meta_description' => 'Privacy description for search engines.',
        'canonical_url' => 'https://mydualist.test/privacy-policy',
    ]);

    $this->get(route('cms.show', $page->slug))
        ->assertOk()
        ->assertSee('<title>Privacy Policy Meta - My Dua List</title>', false)
        ->assertSee('meta name="description" content="Privacy description for search engines."', false)
        ->assertSee('rel="canonical" href="https://mydualist.test/privacy-policy"', false)
        ->assertSee('property="og:title" content="Privacy Policy Meta"', false)
        ->assertSee('property="og:description" content="Privacy description for search engines."', false);

    expect(SeoPresenter::forCmsPage($page)->canonicalUrl)->toBe('https://mydualist.test/privacy-policy');
});

test('cms page seeder creates required pages without overwriting existing content', function () {
    CmsPage::query()->create([
        'slug' => CmsPageSlugs::PRIVACY_POLICY,
        'title' => 'Custom Privacy Title',
        'section' => 'legal',
        'content' => '<p>Existing custom privacy content.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->seed(CmsPageSeeder::class);

    $page = CmsPage::query()->where('slug', CmsPageSlugs::PRIVACY_POLICY)->first();

    expect($page->title)->toBe('Custom Privacy Title')
        ->and($page->content)->toBe('<p>Existing custom privacy content.</p>');

    $this->assertDatabaseHas('cms_pages', [
        'slug' => CmsPageSlugs::TERMS_AND_CONDITIONS,
        'is_published' => true,
    ]);

    $this->assertDatabaseHas('cms_pages', [
        'slug' => CmsPageSlugs::HELP_AND_SUPPORT,
        'is_published' => true,
    ]);
});
