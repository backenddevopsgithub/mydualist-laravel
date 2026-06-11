@props([
    'name',
    'label' => null,
    'description' => null,
    'rows' => 4,
])

<x-ui.field :name="$name" :label="$label" :description="$description" :for="$name" {{ $attributes->only('class') }}>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->except('class')->class([
            'ui-textarea',
            'ui-input--error' => $errors->has($name),
        ]) }}
    >{{ $slot }}</textarea>
</x-ui.field>
