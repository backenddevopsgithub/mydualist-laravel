@props([
    'name',
    'price',
    'period' => null,
    'description',
    'features' => [],
    'highlighted' => false,
    'badge' => null,
    'cta' => 'Get started',
])

<article @class([
    'relative rounded-3xl border bg-white p-7 shadow-[0_22px_70px_rgba(15,23,42,0.07)]',
    'border-emerald-700 ring-4 ring-emerald-100' => $highlighted,
    'border-emerald-950/10' => ! $highlighted,
])>
    @if ($badge)
        <span class="absolute right-6 top-6 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
            {{ $badge }}
        </span>
    @endif

    <h3 class="text-lg font-bold text-stone-950">{{ $name }}</h3>
    <p class="mt-2 min-h-10 text-sm leading-6 text-stone-600">{{ $description }}</p>

    <div class="mt-7 flex items-end gap-1">
        <span class="text-4xl font-bold tracking-tight text-stone-950">{{ $price }}</span>
        @if ($period)
            <span class="pb-1 text-sm font-medium text-stone-500">{{ $period }}</span>
        @endif
    </div>

    <ul class="mt-7 space-y-3 text-sm text-stone-700">
        @foreach ($features as $feature)
            <li class="flex gap-3">
                <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] text-emerald-800">✓</span>
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    <x-home.button
        href="#"
        :variant="$highlighted ? 'primary' : 'secondary'"
        class="mt-8 w-full"
    >
        {{ $cta }}
    </x-home.button>
</article>
