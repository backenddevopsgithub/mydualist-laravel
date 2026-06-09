<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
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

        return view('blogs.index', [
            'categories' => BlogCategory::query()->ordered()->get(),
            'posts' => $query->paginate(9)->withQueryString(),
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
}
