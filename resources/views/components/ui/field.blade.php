@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'for' => null,
])

@php
    $fieldId = $for ?? $name;
@endphp

<div {{ $attributes->class('ui-field') }}>
    @if ($label)
        <label @if ($fieldId) for="{{ $fieldId }}" @endif class="ui-label">{{ $label }}</label>
    @endif

    @if ($description)
        <p class="ui-field-description">{{ $description }}</p>
    @endif

    {{ $slot }}

    @if ($name)
        @error($name)
            <p class="ui-field-error" role="alert">{{ $message }}</p>
        @enderror
    @endif
</div>
