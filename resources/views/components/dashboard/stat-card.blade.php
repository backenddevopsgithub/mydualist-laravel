@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-900',
        'amber' => 'bg-amber-50 text-amber-800',
        'sky' => 'bg-sky-50 text-sky-800',
        'stone' => 'bg-stone-100 text-stone-700',
    ];
@endphp

<article class="min-w-[8.5rem] rounded-3xl border border-emerald-950/10 bg-white p-5 shadow-[0_18px_60px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_80px_rgba(15,23,42,0.08)] sm:min-w-0">
    <div class="flex items-start gap-4">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $tones[$tone] ?? $tones['emerald'] }}">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <div>
            <p class="text-3xl font-extrabold tracking-tight text-stone-950">{{ $value }}</p>
            <p class="mt-1 text-sm font-bold text-stone-800">{{ $label }}</p>
            @if ($description)
                <p class="mt-1 hidden text-xs text-stone-500 sm:block">{{ $description }}</p>
            @endif
        </div>
    </div>
</article>
