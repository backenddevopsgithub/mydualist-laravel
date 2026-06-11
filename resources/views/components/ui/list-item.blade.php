@props([
    'href' => null,
    'title',
    'description' => null,
    'value' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class($href ? 'ui-list-item ui-list-item--interactive' : 'ui-list-item') }}
>
    <div class="ui-list-item__content">
        <p class="ui-list-item__title">{{ $title }}</p>
        @if ($description)
            <p class="ui-list-item__description">{{ $description }}</p>
        @endif
    </div>

    @if ($value)
        <span class="ui-list-item__value">{{ $value }}</span>
    @endif

    @if ($href)
        <span class="ui-list-item__chevron" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    @endif

    {{ $slot }}
</{{ $tag }}>
