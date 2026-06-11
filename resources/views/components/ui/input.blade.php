@props([
    'name',
    'label' => null,
    'description' => null,
    'type' => 'text',
    'value' => null,
])

<x-ui.field :name="$name" :label="$label" :description="$description" :for="$name" {{ $attributes->only('class') }}>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password' && ! $attributes->has('x-model')) value="{{ old($name, $value) }}" @endif
        {{ $attributes->except('class')->class([
            'ui-input',
            'ui-input--error' => $errors->has($name),
        ]) }}
    />
</x-ui.field>
