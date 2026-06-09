<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Browse dua resources, guides, and reminders from My Dua List.">
        <title>Dua Resources - My Dua List</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">
            <div class="reveal-on-scroll">
                <h1 class="font-serif text-5xl font-bold text-stone-950">Dua Resources</h1>
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

            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($posts as $post)
                    <article class="reveal-on-scroll overflow-hidden rounded-2xl border border-emerald-950/10 bg-white shadow-[0_18px_55px_rgba(15,23,42,0.06)] transition hover:-translate-y-1 hover:shadow-xl">
                        <a href="{{ route('blogs.show', $post->slug) }}" class="block">
                            <div class="relative h-72 overflow-hidden">
                                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                                <span class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1 text-sm font-bold text-emerald-950 shadow-sm">{{ $post->category->name }}</span>
                            </div>
                            <div class="p-6">
                                <h2 class="text-2xl font-extrabold leading-snug text-stone-950">{{ $post->title }}</h2>
                                <p class="mt-3 line-clamp-3 text-base leading-7 text-stone-600">{{ $post->excerpt }}</p>
                                <p class="mt-4 text-sm font-semibold text-stone-500">{{ $post->published_at?->format('M j, Y') }} • {{ $post->read_time_minutes }} min read</p>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="col-span-full rounded-2xl bg-white p-8 text-center text-base text-stone-600 ring-1 ring-stone-200">No articles found.</p>
                @endforelse
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
