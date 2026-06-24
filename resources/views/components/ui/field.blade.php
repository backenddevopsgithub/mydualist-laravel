@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'for' => null,
    'required' => false,
])

@php
    $fieldId = $for ?? $name;
@endphp

<div {{ $attributes->class('ui-field') }}>
    @if ($label)
        <label @if ($fieldId) for="{{ $fieldId }}" @endif class="ui-label">
            {{ $label }}@if ($required)<span class="ui-label-required" aria-hidden="true"> *</span>@endif
        </label>
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
