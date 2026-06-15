<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Http\PartialHtmlRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function index(Request $request): View|Response
    {
        [$query, $activeCategory, $search] = $this->buildPostsQuery($request);
        $posts = $query->paginate(9)->withQueryString();

        if (PartialHtmlRequest::wants($request)) {
            return response()
                ->view('blogs.partials.items', compact('posts'))
                ->withHeaders([
                    'X-Infinite-Scroll-Has-More' => $posts->hasMorePages() ? 'true' : 'false',
                    'X-Infinite-Scroll-Next-Page' => $posts->nextPageUrl() ?? '',
                    'X-Infinite-Scroll-Page' => (string) $posts->currentPage(),
                ]);
        }

        return view('blogs.index', [
            'categories' => BlogCategory::query()->ordered()->get(),
            'posts' => $posts,
            'activeCategory' => $activeCategory,
            'search' => $search,
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedPosts = BlogPost::query()
            ->published()
            ->with('category')
            ->where('blog_category_id', $post->blog_category_id)
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blogs.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    /**
     * @return array{0: Builder<BlogPost>, 1: string, 2: string}
     */
    private function buildPostsQuery(Request $request): array
    {
        $activeCategory = $request->string('category')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());

        $query = BlogPost::query()
            ->published()
            ->with('category')
            ->latest('published_at');

        if ($activeCategory !== 'all') {
            $query->whereHas('category', fn ($builder) => $builder->where('slug', $activeCategory));
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return [$query, $activeCategory, $search];
    }
}
