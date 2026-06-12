@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'sky' => 'bg-sky-50 text-sky-700',
        'stone' => 'bg-stone-100 text-stone-600',
    ];
@endphp

<article class="rounded-2xl border border-stone-100 bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.04)] sm:p-5">
    <div class="flex items-start gap-3 sm:gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['emerald'] }}">
            @if ($tone === 'amber')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 8.5h14l-1.5 10.5H6.5L5 8.5ZM9 4.5h6l1 4H8l1-4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                </svg>
            @elseif ($tone === 'sky')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 7.5h14M5 12h14M5 16.5h9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M16.5 16.5 19 19l2.5-2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            @elseif ($tone === 'stone')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="1.5"/>
                </svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @endif
        </span>
        <div class="min-w-0 pt-0.5">
            <p class="text-3xl font-extrabold leading-none tracking-tight text-stone-950">{{ $value }}</p>
            <p class="mt-1.5 text-sm font-bold leading-tight text-stone-950">{{ $label }}</p>
            @if ($description)
                <p class="mt-1 text-xs leading-5 text-stone-500">{{ $description }}</p>
            @endif
        </div>
    </div>
</article>
