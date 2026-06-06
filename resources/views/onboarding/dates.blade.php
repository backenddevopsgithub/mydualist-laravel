<x-onboarding.layout
    step="dates"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Set Your Dates"
    subtitle="When is your trip or occasion? This helps people plan and send dua requests on time."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'dates') }}"
        x-data="{
            startDate: @js(old('start_date', data_get($state, 'dates.start_date'))),
            endDate: @js(old('end_date', data_get($state, 'dates.end_date'))),
            openPicker(ref) {
                this.$refs[ref].showPicker ? this.$refs[ref].showPicker() : this.$refs[ref].focus()
            },
        }"
    >
        @csrf

        <div class="space-y-6">
            <div>
                <label for="start_date" class="block text-[1.075rem] font-bold text-stone-900">Start Date</label>
                <div class="relative mt-2">
                    <input
                        x-ref="start"
                        x-model="startDate"
                        x-on:click="openPicker('start')"
                        id="start_date"
                        name="start_date"
                        type="date"
                        class="block w-full cursor-pointer rounded-2xl border border-stone-200 bg-white px-5 py-4 pr-14 text-[1.075rem] text-stone-900 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100"
                        required
                    >
                    <button type="button" x-on:click="openPicker('start')" class="absolute inset-y-2 right-2 flex w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4v3M17 4v3M5 9h14M6.5 6h11A1.5 1.5 0 0 1 19 7.5v10A1.5 1.5 0 0 1 17.5 19h-11A1.5 1.5 0 0 1 5 17.5v-10A1.5 1.5 0 0 1 6.5 6Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                @error('start_date')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="end_date" class="block text-[1.075rem] font-bold text-stone-900">End Date</label>
                <div class="relative mt-2">
                    <input
                        x-ref="end"
                        x-model="endDate"
                        x-bind:min="startDate || null"
                        x-on:click="openPicker('end')"
                        id="end_date"
                        name="end_date"
                        type="date"
                        class="block w-full cursor-pointer rounded-2xl border border-stone-200 bg-white px-5 py-4 pr-14 text-[1.075rem] text-stone-900 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100"
                        required
                    >
                    <button type="button" x-on:click="openPicker('end')" class="absolute inset-y-2 right-2 flex w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4v3M17 4v3M5 9h14M6.5 6h11A1.5 1.5 0 0 1 19 7.5v10A1.5 1.5 0 0 1 17.5 19h-11A1.5 1.5 0 0 1 5 17.5v-10A1.5 1.5 0 0 1 6.5 6Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                @error('end_date')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                Put your end date a few days before your trip finishes so you have enough time to complete all dua requests.
            </div>
        </div>

        <x-onboarding.actions back="list" />
    </form>
</x-onboarding.layout>
