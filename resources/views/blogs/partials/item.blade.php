<article
    class="reveal-on-scroll is-visible overflow-hidden rounded-2xl border border-emerald-950/10 bg-white shadow-[0_18px_55px_rgba(15,23,42,0.06)] transition hover:-translate-y-1 hover:shadow-xl"
    data-feed-item
    data-feed-item-id="{{ $post->id }}"
    data-feed-item-index="{{ $itemIndex }}"
>
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
