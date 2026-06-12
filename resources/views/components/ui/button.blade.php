@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'fullWidth' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $variantClass = match ($variant) {
        'secondary' => 'ui-btn--secondary',
        'neutral' => 'ui-btn--neutral',
        default => 'ui-btn--primary',
    };
    $sizeClass = match ($size) {
        'sm' => 'ui-btn--sm',
        'lg' => 'ui-btn--lg',
        default => 'ui-btn--md',
    };
    $classes = collect([
        'ui-btn',
        $variantClass,
        $sizeClass,
        $fullWidth ? 'ui-btn--full' : null,
    ])->filter()->implode(' ');
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
    @else
        type="{{ $type }}"
    @endif
    {{ $attributes->class($classes) }}
>
    {{ $slot }}
</{{ $tag }}>
