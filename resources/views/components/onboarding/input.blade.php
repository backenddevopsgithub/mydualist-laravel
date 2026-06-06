@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
])

<div>
    <label for="{{ $name }}" class="block text-[1.075rem] font-bold text-stone-900">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->class([
            'mt-2 block w-full rounded-xl border bg-white px-4 py-3.5 text-[1.075rem] text-stone-900 shadow-sm outline-none transition placeholder:text-[1.075rem] placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100',
            'border-red-300' => $errors->has($name),
            'border-stone-200' => ! $errors->has($name),
        ]) }}
    >
    @error($name)
        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
