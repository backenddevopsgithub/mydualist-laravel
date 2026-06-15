<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Str;

function createPublishedBlogPost(BlogCategory $category, string $title, int $daysAgo = 1): BlogPost
{
    return BlogPost::query()->create([
        'blog_category_id' => $category->id,
        'title' => $title,
        'slug' => Str::slug($title),
        'excerpt' => "Excerpt for {$title}.",
        'content' => "<p>Content for {$title}.</p>",
        'featured_image' => 'images/blog/prayer.jpg',
        'read_time_minutes' => 5,
        'is_published' => true,
        'published_at' => now()->subDays($daysAgo),
    ]);
}

function seedBlogPostsForPagination(int $count = 12): BlogCategory
{
    $category = BlogCategory::query()->create([
        'name' => 'Daily Duas',
        'slug' => 'daily-duas',
        'sort_order' => 1,
    ]);

    for ($index = 1; $index <= $count; $index++) {
        createPublishedBlogPost($category, "Pagination Test Article {$index}", $index);
    }

    return $category;
}

test('blog index paginates published posts for standard requests', function () {
    seedBlogPostsForPagination(12);

    $firstPage = $this->get(route('blogs.index'))->assertOk();

    $firstPage->assertSee('Pagination Test Article 1', false)
        ->assertSee('Pagination Test Article 9', false);

    preg_match_all('/data-feed-item-id="\d+"/', $firstPage->getContent(), $firstPageItems);
    expect($firstPageItems[0])->toHaveCount(9);

    $secondPage = $this->get(route('blogs.index', ['page' => 2]))->assertOk();

    $secondPage->assertSee('Pagination Test Article 10', false)
        ->assertSee('Pagination Test Article 12', false);

    preg_match_all('/data-feed-item-id="\d+"/', $secondPage->getContent(), $secondPageItems);
    expect($secondPageItems[0])->toHaveCount(3);
});

test('blog index keeps crawlable pagination links and rel next prev tags', function () {
    seedBlogPostsForPagination(12);

    $this->get(route('blogs.index'))
        ->assertOk()
        ->assertSee('rel="next"', false)
        ->assertSee('page=2', false)
        ->assertDontSee('rel="prev"', false);

    $this->get(route('blogs.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('rel="prev"', false)
        ->assertDontSee('rel="next"', false);
});

test('blog index returns item partials for ajax requests', function () {
    seedBlogPostsForPagination(12);

    $response = $this->get(route('blogs.index', ['page' => 2]), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response
        ->assertOk()
        ->assertHeader('X-Infinite-Scroll-Has-More', 'false')
        ->assertHeader('X-Infinite-Scroll-Page', '2')
        ->assertHeader('X-Infinite-Scroll-Next-Page', '')
        ->assertSee('data-feed-item', false)
        ->assertSee('Pagination Test Article 10', false)
        ->assertSee('Pagination Test Article 12', false)
        ->assertDontSee('<!DOCTYPE html>', false)
        ->assertDontSee('Dua Resources', false);
});

test('blog index returns item partials for text html partial accept header', function () {
    seedBlogPostsForPagination(12);

    $this->get(route('blogs.index', ['page' => 2]), [
        'Accept' => 'text/html+partial',
    ])
        ->assertOk()
        ->assertSee('Pagination Test Article 10', false)
        ->assertDontSee('<!DOCTYPE html>', false);
});

test('blog index partial responses include next page metadata when more pages exist', function () {
    seedBlogPostsForPagination(12);

    $this->get(route('blogs.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertHeader('X-Infinite-Scroll-Has-More', 'true')
        ->assertHeader('X-Infinite-Scroll-Page', '1')
        ->assertHeader('X-Infinite-Scroll-Next-Page', route('blogs.index', ['page' => 2]));
});

test('blog index partial responses do not duplicate items across pages', function () {
    seedBlogPostsForPagination(12);

    $pageOne = $this->get(route('blogs.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->getContent();

    $pageTwo = $this->get(route('blogs.index', ['page' => 2]), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->getContent();

    preg_match_all('/data-feed-item-id="(\d+)"/', $pageOne, $pageOneIds);
    preg_match_all('/data-feed-item-id="(\d+)"/', $pageTwo, $pageTwoIds);

    expect($pageOneIds[1])->toHaveCount(9)
        ->and($pageTwoIds[1])->toHaveCount(3)
        ->and(array_intersect($pageOneIds[1], $pageTwoIds[1]))->toBeEmpty();
});

test('blog index end of results partial reports no more pages', function () {
    seedBlogPostsForPagination(12);

    $this->get(route('blogs.index', ['page' => 2]), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertHeader('X-Infinite-Scroll-Has-More', 'false')
        ->assertHeader('X-Infinite-Scroll-Next-Page', '');
});

test('blog index infinite scroll markup is present on full page responses', function () {
    seedBlogPostsForPagination(12);

    $this->get(route('blogs.index'))
        ->assertOk()
        ->assertSee('data-infinite-scroll', false)
        ->assertSee('data-infinite-scroll-sentinel', false)
        ->assertSee('data-infinite-scroll-pagination-fallback', false)
        ->assertSee('No more articles to show.', false);
});
