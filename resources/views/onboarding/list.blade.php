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
            occasion: @js($selected ?? ''),
            showCreatorModal: false,
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
                <p class="block text-[1.075rem] font-bold text-stone-900">
                    Occasion<span class="ui-label-required" aria-hidden="true"> *</span>
                </p>
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

            @if (($creatorModeEnabled ?? false) && ! ($creatorMode ?? false))
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="text-sm font-bold text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-emerald-700"
                        x-on:click="showCreatorModal = true"
                    >
                        Explore Creator Mode
                    </button>
                </div>
            @endif

            @if ($creatorMode ?? false)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                    Using Creator Mode
                </div>
            @endif
        </div>

        <x-onboarding.actions back="account" submit="Next" />

        @if ($creatorModeEnabled ?? false)
            <div
                x-cloak
                x-show="showCreatorModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl sm:p-8">
                    <h3 class="text-2xl font-extrabold text-stone-950">Switch to creator mode?</h3>
                    <p class="mt-4 text-sm leading-7 text-stone-600">
                        Creator mode is a paid option built for content creators, influencers and fundraisers in mind.
                    </p>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-7 text-stone-700">
                        <li>All the great features in MyDuaList</li>
                        <li>Add your own fundraising link and collect donations</li>
                        <li>See views, clicks and insights</li>
                    </ol>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="rounded-2xl px-5 py-3 text-sm font-bold text-stone-700" x-on:click="showCreatorModal = false">Cancel</button>
                        <a
                            :href="`{{ route('onboarding.creator.start') }}?List_Name=${encodeURIComponent(title)}&Category_name=${encodeURIComponent(occasion)}`"
                            class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white"
                        >Use Creator Mode</a>
                    </div>
                </div>
            </div>
        @endif
    </form>
</x-onboarding.layout>
