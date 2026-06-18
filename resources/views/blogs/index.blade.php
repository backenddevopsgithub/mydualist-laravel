<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :seo="$seo" />
        @if ($posts->previousPageUrl())
            <link rel="prev" href="{{ $posts->previousPageUrl() }}">
        @endif
        @if ($posts->nextPageUrl())
            <link rel="next" href="{{ $posts->nextPageUrl() }}">
        @endif
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">
            <div class="reveal-on-scroll">
                <h1 class="dashboard-page-title text-stone-950">Dua Resources</h1>
                <p class="mt-4 max-w-2xl text-xl leading-8 text-stone-600">Articles, guides, and reminders to help you stay consistent with your duas.</p>

                <form method="GET" action="{{ route('blogs.index') }}" class="mt-8 flex max-w-xl gap-3">
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search for an article" class="min-w-0 flex-1 rounded-xl border border-emerald-950/10 bg-white px-4 py-3.5 text-base outline-none ring-emerald-900/5 focus:ring-2">
                    <button type="submit" class="rounded-xl bg-emerald-950 px-5 py-3.5 text-base font-bold text-white">Search</button>
                </form>

                <div class="mt-8 flex flex-wrap gap-2 text-sm font-bold">
                    <a href="{{ route('blogs.index', array_filter(['search' => $search ?: null])) }}" @class(['rounded-full px-4 py-2 transition', $activeCategory === 'all' ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'])>All</a>
                    @foreach ($categories as $category)
                        <a href="{{ route('blogs.index', array_filter(['category' => $category->slug, 'search' => $search ?: null])) }}" @class(['rounded-full px-4 py-2 transition', $activeCategory === $category->slug ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'])>{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div
                data-infinite-scroll
                data-next-page-url="{{ $posts->nextPageUrl() ?? '' }}"
                data-end-message="No more articles to show."
            >
                <div data-infinite-scroll-items class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($posts as $post)
                        @include('blogs.partials.item', [
                            'post' => $post,
                            'itemIndex' => ($posts->firstItem() ?? 0) + $loop->index,
                        ])
                    @empty
                        <p class="col-span-full rounded-2xl bg-white p-8 text-center text-base text-stone-600 ring-1 ring-stone-200">No articles found.</p>
                    @endforelse
                </div>

                <div data-infinite-scroll-loading class="mt-10 hidden" aria-live="polite" aria-busy="true">
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="animate-pulse overflow-hidden rounded-2xl border border-emerald-950/10 bg-white shadow-[0_18px_55px_rgba(15,23,42,0.06)]" aria-hidden="true">
                                <div class="h-72 bg-stone-200"></div>
                                <div class="space-y-3 p-6">
                                    <div class="h-7 rounded bg-stone-200"></div>
                                    <div class="h-4 rounded bg-stone-200"></div>
                                    <div class="h-4 w-5/6 rounded bg-stone-200"></div>
                                    <div class="h-4 w-2/5 rounded bg-stone-200"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div data-infinite-scroll-end class="mt-10 hidden rounded-2xl bg-white p-6 text-center text-base text-stone-600 ring-1 ring-stone-200" aria-live="polite">
                    No more articles to show.
                </div>

                <div data-infinite-scroll-error class="mt-10 hidden rounded-2xl bg-white p-6 text-center ring-1 ring-stone-200" aria-live="assertive">
                    <p class="text-base text-stone-600">We could not load more articles.</p>
                    <button type="button" data-infinite-scroll-retry class="mt-4 rounded-xl bg-emerald-950 px-5 py-3 text-base font-bold text-white">Retry</button>
                </div>

                <div data-infinite-scroll-sentinel class="h-px w-full" aria-hidden="true"></div>

                <nav data-infinite-scroll-pagination-fallback class="mt-10" aria-label="Blog pagination">
                    {{ $posts->links() }}
                </nav>
            </div>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
