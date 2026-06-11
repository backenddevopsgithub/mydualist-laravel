@props([
    'name',
    'label' => null,
    'description' => null,
])

<x-ui.field :name="$name" :label="$label" :description="$description" :for="$name" {{ $attributes->only('class') }}>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->except('class')->class([
            'ui-select',
            'ui-input--error' => $errors->has($name),
        ]) }}
    >
        {{ $slot }}
    </select>
</x-ui.field>
