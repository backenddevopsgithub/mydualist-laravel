@php
    $occasions = [
        'umrah' => 'Umrah',
        'hajj' => 'Hajj',
        'ramadan' => 'Ramadan',
        'safar-travel' => 'Safar / Travel',
        'wedding' => 'Wedding',
        'aqiqah' => 'Aqiqah',
        'tahajjud' => 'Tahajjud',
        'quran-khatam' => 'Quran Khatam',
        'other' => 'Other',
    ];

    $selected = old('occasion', data_get($state, 'category.occasion'));
@endphp

<x-onboarding.layout
    step="category"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Choose a Category"
    subtitle="Select the occasion that best describes this list."
>
    <form method="POST" action="{{ route('onboarding.store', 'category') }}" x-data="{ selected: @js($selected) }">
        @csrf

        <div>
            <p class="block text-[1.075rem] font-bold text-stone-900">Occasion</p>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($occasions as $value => $label)
                    <label
                        class="cursor-pointer rounded-2xl border px-4 py-4 text-center text-[1.075rem] font-semibold transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-sm"
                        x-bind:class="selected === '{{ $value }}'
                            ? 'border-emerald-700 bg-emerald-50 text-emerald-950 ring-4 ring-emerald-100'
                            : 'border-stone-200 bg-white text-stone-700'"
                    >
                        <input
                            type="radio"
                            name="occasion"
                            value="{{ $value }}"
                            class="sr-only"
                            x-model="selected"
                        >
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('occasion')
                <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <x-onboarding.actions back="list" />
    </form>
</x-onboarding.layout>
