@props([
    'href' => null,
    'active' => false,
    'name' => null,
])

@if ($href)
    <a
        href="{{ $href }}"
        role="tab"
        aria-selected="{{ $active ? 'true' : 'false' }}"
        {{ $attributes->class($active ? 'ui-tab ui-tab--active' : 'ui-tab') }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="button"
        role="tab"
        @if ($name)
            x-on:click="tab = '{{ $name }}'"
            x-bind:aria-selected="tab === '{{ $name }}' ? 'true' : 'false'"
            x-bind:class="tab === '{{ $name }}' ? 'ui-tab ui-tab--active' : 'ui-tab'"
        @endif
        {{ $attributes->class($name ? 'ui-tab' : ($active ? 'ui-tab ui-tab--active' : 'ui-tab')) }}
    >
        {{ $slot }}
    </button>
@endif
