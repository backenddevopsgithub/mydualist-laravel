<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :seo="$seo" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header-blog')

        <main class="mx-auto max-w-6xl px-5 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('blogs.index', ['category' => $post->category->slug]) }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-800 transition hover:text-emerald-700">
                <span aria-hidden="true">←</span>
                Back to Dua Resources
            </a>

            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <article class="reveal-on-scroll min-w-0">
                    <span class="inline-flex rounded-full bg-emerald-100 px-4 py-2 text-sm font-bold text-emerald-900">{{ $post->category->name }}</span>
                    <h1 class="dashboard-page-title mt-6 text-stone-950">{{ $post->title }}</h1>
                    <p class="mt-4 text-base font-semibold text-stone-500">{{ $post->published_at?->format('F j, Y') }} • {{ $post->read_time_minutes }} min read</p>

                    <div class="mt-8 overflow-hidden rounded-3xl shadow-xl ring-1 ring-emerald-950/10">
                        <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="h-[28rem] w-full object-cover">
                    </div>

                    <div class="blog-content prose prose-lg prose-stone mt-10 max-w-none text-xl leading-9">
                        {!! $post->content !!}
                    </div>

                    @include('blogs.partials.faqs', ['post' => $post])

                    @if ($relatedPosts->isNotEmpty())
                        <section class="mt-16 border-t border-emerald-950/10 pt-12">
                            <h2 class="font-serif text-3xl font-bold text-stone-950">Related Articles</h2>
                            <div class="mt-6 grid gap-6 md:grid-cols-2">
                                @foreach ($relatedPosts as $related)
                                    <a href="{{ route('blogs.show', $related->slug) }}" class="rounded-2xl border border-emerald-950/10 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                                        <img src="{{ $related->featuredImageUrl() }}" alt="{{ $related->title }}" class="h-40 w-full rounded-xl object-cover">
                                        <h3 class="mt-4 text-lg font-extrabold text-stone-950">{{ $related->title }}</h3>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @include('blogs.partials.newsletter')
                </article>

                <aside class="space-y-6 lg:sticky lg:top-28">
                    @include('blogs.partials.support-our-cause', [
                        'supportOurCause' => $supportOurCause,
                        'supportOurCauseImageUrl' => $supportOurCauseImageUrl,
                    ])
                </aside>
            </div>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
