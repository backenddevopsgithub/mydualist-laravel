@props([
    'label',
    'title',
    'description',
    'tone' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'from-emerald-100 via-emerald-50 to-white text-emerald-800',
        'stone' => 'from-stone-200 via-stone-100 to-white text-stone-700',
        'amber' => 'from-amber-100 via-orange-50 to-white text-amber-800',
        'sky' => 'from-sky-100 via-slate-50 to-white text-sky-800',
    ];
@endphp

<article class="group overflow-hidden rounded-3xl border border-emerald-950/10 bg-white shadow-[0_22px_60px_rgba(15,23,42,0.06)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_26px_80px_rgba(15,23,42,0.1)]">
    <div class="relative h-40 bg-gradient-to-br {{ $tones[$tone] }}">
        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold shadow-sm">
            {{ $label }}
        </span>
        <div class="absolute inset-x-6 bottom-5 grid grid-cols-3 gap-3">
            <span class="h-20 rounded-2xl bg-white/60 shadow-sm"></span>
            <span class="h-28 rounded-2xl bg-white/80 shadow-sm"></span>
            <span class="h-16 rounded-2xl bg-white/50 shadow-sm"></span>
        </div>
    </div>

    <div class="p-5">
        <h3 class="text-base font-bold text-stone-950">{{ $title }}</h3>
        <p class="mt-2 text-sm leading-6 text-stone-600">{{ $description }}</p>
    </div>
</article>
