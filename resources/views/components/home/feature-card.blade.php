@props([
    'icon' => 'check',
    'title',
    'description',
    'items' => [],
])

<article {{ $attributes->merge(['class' => 'rounded-3xl border border-emerald-950/10 bg-white p-7 shadow-[0_22px_70px_rgba(15,23,42,0.06)]']) }}>
    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800">
        @if ($icon === 'lock')
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8"/>
            </svg>
        @elseif ($icon === 'bell')
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 9.75a6 6 0 1 0-12 0c0 6-2 6.75-2 6.75h16s-2-.75-2-6.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M10 20a2.3 2.3 0 0 0 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        @else
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        @endif
    </div>

    <h3 class="mt-6 text-lg font-bold text-stone-950">{{ $title }}</h3>
    <p class="mt-3 text-sm leading-6 text-stone-600">{{ $description }}</p>

    @if ($items)
        <ul class="mt-6 space-y-3 text-sm text-stone-700">
            @foreach ($items as $item)
                <li class="flex gap-3">
                    <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] text-emerald-800">✓</span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</article>
