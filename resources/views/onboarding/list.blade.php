@php
    $occasions = \App\Support\DuaListOccasions::labels();
    $selected = old('occasion', data_get($state, 'list.occasion'));
@endphp

<x-onboarding.layout
    step="list"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Create Your List"
    subtitle="Give your list a meaningful name and choose the occasion."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'list') }}"
        x-data="{
            title: @js(old('title', data_get($state, 'list.title', ''))),
            occasion: @js($selected),
            get canSubmit() {
                return this.title.trim() !== '' && this.occasion !== '';
            },
        }"
    >
        @csrf

        <div class="space-y-6">
            <x-onboarding.input
                name="title"
                label="List Name"
                placeholder="e.g. Hajj 2027"
                required
                autofocus
                x-model="title"
            />

            <div>
                <p class="block text-[1.075rem] font-bold text-stone-900">Occasion</p>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($occasions as $value => $label)
                        <label
                            class="cursor-pointer rounded-2xl border px-4 py-4 text-center text-[1.075rem] font-semibold transition hover:-translate-y-0.5 hover:shadow-sm"
                            x-bind:class="occasion === '{{ $value }}'
                                ? 'border-emerald-800 bg-emerald-800 text-white shadow-md'
                                : 'border-stone-200 bg-white text-stone-700 hover:border-emerald-300'"
                        >
                            <input type="radio" name="occasion" value="{{ $value }}" class="sr-only" x-model="occasion" required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('occasion')
                    <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                Choose a simple name your friends and family will recognise.
            </div>
        </div>

        <x-onboarding.actions back="account" submit="Next" x-bind:disabled="! canSubmit" />
    </form>
</x-onboarding.layout>
