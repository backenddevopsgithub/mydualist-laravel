@props([
    'href' => '#',
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2';
    $sizes = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-5 py-3 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];
    $variants = [
        'primary' => 'bg-emerald-800 text-white shadow-sm shadow-emerald-950/10 hover:bg-emerald-700',
        'secondary' => 'border border-stone-200 bg-white text-stone-900 shadow-sm hover:border-emerald-200 hover:text-emerald-800',
        'ghost' => 'text-stone-700 hover:bg-emerald-50 hover:text-emerald-800',
        'dark' => 'bg-emerald-950 text-white hover:bg-emerald-900',
    ];
@endphp

<a {{ $attributes->merge([
    'href' => $href,
    'class' => $base . ' ' . $sizes[$size] . ' ' . $variants[$variant],
]) }}>
    {{ $slot }}
</a>
