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
        @include('partials.marketing-header')

        <main class="mx-auto max-w-4xl px-5 py-12 sm:px-6 lg:px-8">
            <article class="reveal-on-scroll rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_22px_70px_rgba(15,23,42,0.08)] sm:p-10">
                <h1 class="dashboard-page-title text-stone-950">{{ $page->title }}</h1>

                @if ($page->updated_at)
                    <p class="mt-4 text-sm font-semibold text-stone-500">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
                @endif

                @if ($page->excerpt)
                    <p class="mt-6 text-xl leading-8 text-stone-600">{{ $page->excerpt }}</p>
                @endif

                @if ($page->content)
                    <div class="prose prose-lg prose-stone mt-8 max-w-none text-lg leading-8">
                        {!! $page->content !!}
                    </div>
                @endif
            </article>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
