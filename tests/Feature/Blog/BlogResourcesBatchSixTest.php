<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('blog index category filter returns partial feed without full page shell', function () {
    $category = BlogCategory::query()->create([
        'name' => 'Health',
        'slug' => 'health',
        'sort_order' => 1,
        'show_in_resources_filter' => true,
    ]);

    BlogCategory::query()->create([
        'name' => 'Daily Duas',
        'slug' => 'daily-duas',
        'sort_order' => 0,
        'show_in_resources_filter' => false,
    ]);

    BlogPost::query()->create([
        'blog_category_id' => $category->id,
        'title' => 'Healing Duas',
        'slug' => 'healing-duas',
        'excerpt' => 'Hope and recovery.',
        'content' => '<p>Content</p>',
        'read_time_minutes' => 5,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('blogs.index'))
        ->assertOk()
        ->assertSee('data-blog-filters', false)
        ->assertSee('data-blog-category="health"', false)
        ->assertDontSee('data-blog-category="daily-duas"', false)
        ->assertDontSee('How it works', false);

    $this->get(route('blogs.index', ['category' => 'health']), [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html+partial',
        'X-Blog-Filter' => 'category',
    ])
        ->assertOk()
        ->assertSee('data-blog-feed', false)
        ->assertSee('Healing Duas', false)
        ->assertDontSee('<!DOCTYPE html>', false);
});

test('blog article page shows back link newsletter support section and faqs', function () {
    $category = BlogCategory::query()->create([
        'name' => 'Health',
        'slug' => 'health',
        'sort_order' => 1,
        'show_in_resources_filter' => true,
    ]);

    $post = BlogPost::query()->create([
        'blog_category_id' => $category->id,
        'title' => 'Healing Duas',
        'slug' => 'healing-duas',
        'excerpt' => 'Hope and recovery.',
        'content' => '<p>Article body</p>',
        'faqs' => [
            ['question' => 'How often should I read this?', 'answer' => 'Daily if possible.'],
        ],
        'read_time_minutes' => 5,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('blogs.show', $post->slug))
        ->assertOk()
        ->assertSee('Back to Dua Resources', false)
        ->assertSee('Get one Dua a week in your inbox.', false)
        ->assertSee('Support Our Cause', false)
        ->assertSee('How often should I read this?', false)
        ->assertSee('FAQPage', false)
        ->assertDontSee('How it works', false);
});

test('article newsletter signup stores subscription with article source', function () {
    $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
        'source' => 'article',
    ])->assertRedirect();

    expect(NewsletterSubscription::query()->where('email', 'reader@example.com')->value('source'))
        ->toBe('article');
});

test('featured image url falls back when local file is missing', function () {
    $category = BlogCategory::query()->create([
        'name' => 'Health',
        'slug' => 'health',
        'sort_order' => 1,
        'show_in_resources_filter' => true,
    ]);

    $post = BlogPost::query()->create([
        'blog_category_id' => $category->id,
        'title' => 'Healing Duas',
        'slug' => 'healing-duas',
        'excerpt' => 'Hope and recovery.',
        'content' => '<p>Article body</p>',
        'featured_image' => 'images/blog/missing.jpg',
        'read_time_minutes' => 5,
        'is_published' => true,
        'published_at' => now(),
    ]);

    expect($post->featuredImageUrl())->toStartWith('https://');
});
