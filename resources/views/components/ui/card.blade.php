@props([
    'padding' => 'default',
    'bordered' => true,
])

@php
    $classes = collect([
        'ui-card',
        $padding === 'none' ? 'ui-card--flush' : null,
        ! $bordered ? 'ui-card--flat' : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->class($classes) }}>
    {{ $slot }}
</div>
