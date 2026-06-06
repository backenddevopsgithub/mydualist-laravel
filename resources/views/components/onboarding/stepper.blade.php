@props([
    'current',
    'total',
])

<div class="mx-auto flex max-w-md items-center justify-center gap-2 sm:gap-3" aria-label="Onboarding progress">
    @for ($i = 1; $i <= $total; $i++)
        <div class="flex items-center gap-2 sm:gap-3">
            <span @class([
                'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition',
                'bg-emerald-800 text-white shadow-sm shadow-emerald-950/10' => $i === $current,
                'bg-emerald-50 text-emerald-800' => $i < $current,
                'bg-stone-100 text-stone-400' => $i > $current,
            ])>
                {{ $i < $current ? '✓' : $i }}
            </span>
            @if ($i < $total)
                <span @class([
                    'hidden h-px w-6 sm:block',
                    'bg-emerald-700' => $i < $current,
                    'bg-stone-200' => $i >= $current,
                ])></span>
            @endif
        </div>
    @endfor
</div>
