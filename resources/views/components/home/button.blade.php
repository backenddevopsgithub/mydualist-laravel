@props([
    'href' => '#',
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $uiVariant = match ($variant) {
        'ghost' => 'neutral',
        'dark' => 'primary',
        default => $variant,
    };
    $uiSize = match ($size) {
        'lg' => 'lg',
        'sm' => 'sm',
        default => 'md',
    };
@endphp

<x-ui.button :href="$href" :variant="$uiVariant" :size="$uiSize" {{ $attributes }}>
    {{ $slot }}
</x-ui.button>
