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
        @include('partials.marketing-header-blog')

        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">
            <div class="reveal-on-scroll">
                <h1 class="dashboard-page-title text-stone-950">Dua Resources</h1>
                <p class="mt-4 max-w-2xl text-xl leading-8 text-stone-600">Articles, guides, and reminders to help you stay consistent with your duas.</p>

                <form method="GET" action="{{ route('blogs.index') }}" class="mt-8 flex max-w-xl gap-3">
                    <input type="hidden" name="category" value="{{ $activeCategory === 'all' ? '' : $activeCategory }}" data-blog-search-category>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search for an article" data-blog-search-input class="min-w-0 flex-1 rounded-xl border border-emerald-950/10 bg-white px-4 py-3.5 text-base outline-none ring-emerald-900/5 focus:ring-2">
                    <button type="submit" class="rounded-xl bg-emerald-950 px-5 py-3.5 text-base font-bold text-white">Search</button>
                </form>

                <div class="mt-8 flex flex-wrap gap-2 text-sm font-bold" data-blog-filters>
                    <button
                        type="button"
                        data-blog-category="all"
                        aria-pressed="{{ $activeCategory === 'all' ? 'true' : 'false' }}"
                        @class(['rounded-full px-4 py-2 transition', $activeCategory === 'all' ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'])
                    >All</button>
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            data-blog-category="{{ $category->slug }}"
                            aria-pressed="{{ $activeCategory === $category->slug ? 'true' : 'false' }}"
                            @class(['rounded-full px-4 py-2 transition', $activeCategory === $category->slug ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'])
                        >{{ $category->name }}</button>
                    @endforeach
                </div>
            </div>

            @include('blogs.partials.feed', ['posts' => $posts])
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
