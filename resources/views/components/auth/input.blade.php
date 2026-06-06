@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'icon' => 'mail',
    'autocomplete' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-bold text-stone-900">{{ $label }}</label>
    <div class="relative mt-3">
        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-stone-400">
            @if ($icon === 'lock')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 10V8a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M7 10h10v9H7v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4.5 6.5h15v11h-15v-11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="m5 7 7 6 7-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @endif
        </span>

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            placeholder="{{ $placeholder }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->class([
                'block w-full rounded-xl border bg-white py-4 pl-12 pr-4 text-base text-stone-900 shadow-sm outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100',
                'border-red-300' => $errors->has($name),
                'border-stone-200' => ! $errors->has($name),
            ]) }}
        >
    </div>

    @error($name)
        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
