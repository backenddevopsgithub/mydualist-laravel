@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'center',
])

@php
    $alignment = $align === 'left' ? 'text-left items-start' : 'text-center items-center mx-auto';
@endphp

<div {{ $attributes->merge(['class' => 'flex max-w-3xl flex-col ' . $alignment]) }}>
    @if ($eyebrow)
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ $eyebrow }}</p>
    @endif

    <h2 class="mt-3 text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">
        {{ $title }}
    </h2>

    @if ($description)
        <p class="mt-4 max-w-2xl text-base leading-7 text-stone-600">
            {{ $description }}
        </p>
    @endif
</div>
