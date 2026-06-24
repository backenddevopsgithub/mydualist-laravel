@props([
    'name',
    'label' => null,
    'description' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
])

@php
    $inputAttributes = $attributes
        ->except('class')
        ->merge([
            'id' => $name,
            'name' => $name,
            'type' => $type,
            'class' => collect([
                'ui-input',
                $errors->has($name) ? 'ui-input--error' : null,
            ])->filter()->implode(' '),
        ]);

    if ($type !== 'password' && ! $attributes->has('x-model')) {
        $inputAttributes = $inputAttributes->merge(['value' => old($name, $value)], escape: false);
    }

    if ($placeholder) {
        $inputAttributes = $inputAttributes->merge(['placeholder' => $placeholder], escape: false);
    }
@endphp

<x-ui.field :name="$name" :label="$label" :description="$description" :for="$name" :required="$required" {{ $attributes->only('class') }}>
    <input {{ $inputAttributes }} />
</x-ui.field>
