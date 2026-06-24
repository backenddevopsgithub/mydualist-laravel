<div
    data-blog-feed
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
