@php
    $startDate = old('start_date', data_get($state, 'dates.start_date'));
    $endDate = old('end_date', data_get($state, 'dates.end_date'));
@endphp

<x-onboarding.layout
    step="dates"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Set Your Dates"
    subtitle="When is your trip or occasion? This helps people plan and send dua requests on time."
>
    <form method="POST" action="{{ route('onboarding.store', 'dates') }}" id="dates-form">
        @csrf

        <div class="space-y-6">
            <x-ui.field name="start_date" label="Start date">
                <input
                    id="start_date"
                    name="start_date"
                    type="text"
                    value="{{ $startDate }}"
                    class="ui-date-input @error('start_date') ui-input--error @enderror"
                    required
                    readonly
                    autocomplete="off"
                >
            </x-ui.field>

            <x-ui.field name="end_date" label="End date">
                <input
                    id="end_date"
                    name="end_date"
                    type="text"
                    value="{{ $endDate }}"
                    class="ui-date-input @error('end_date') ui-input--error @enderror"
                    required
                    readonly
                    autocomplete="off"
                >
            </x-ui.field>

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                Set your end date a few days before your trip finishes so you have time to complete all dua requests.
            </div>
        </div>

        <x-onboarding.actions back="list" submit="Next" />
    </form>
</x-onboarding.layout>
