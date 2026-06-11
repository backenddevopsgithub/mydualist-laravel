@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'fullWidth' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $classes = collect([
        'ui-btn',
        'ui-btn--' . $variant,
        'ui-btn--' . $size,
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
