@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'description' => null,
])

<x-ui.input
    :name="$name"
    :label="$label"
    :type="$type"
    :value="$value"
    :description="$description"
    :placeholder="$placeholder"
    {{ $attributes }}
/>
