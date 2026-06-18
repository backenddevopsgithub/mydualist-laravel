<x-onboarding.layout
    step="fundraising"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Add a donation link"
    subtitle="Connect your list to a fundraising campaign on a supported platform."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'fundraising') }}"
        x-data="{
            donationLink: @js(old('donation_link', data_get($state, 'fundraising.donation_link', ''))),
            donationNote: @js(old('donation_note', data_get($state, 'fundraising.donation_note', ''))),
            linkPattern: @js(\App\Support\CreatorMode::donationLinkPattern()),
            get linkValid() {
                if (this.donationLink.trim() === '') return false;
                return new RegExp(this.linkPattern).test(this.donationLink.trim());
            },
            get canSubmit() {
                return this.linkValid && this.donationNote.trim() !== '';
            },
        }"
    >
        @csrf

        <div class="space-y-6">
            <div>
                <x-onboarding.input
                    name="donation_link"
                    label="Donation link URL"
                    type="url"
                    placeholder="https://www.launchgood.com/safe_water_for_gaza"
                    x-model="donationLink"
                    required
                />
                <p class="mt-2 text-sm text-stone-500">
                    Supported platforms:
                    LaunchGood, JustGiving, GoFundMe, GiveMatch, MuslimGiving, and Givebrite.
                </p>
                @error('donation_link')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-onboarding.input
                    name="donation_note"
                    label="Donation note"
                    placeholder="Enter text"
                    x-model="donationNote"
                    required
                />
                <p class="mt-2 text-sm text-stone-500">
                    We’ll show this note to dua submitters at the point of submission and on their dua completion emails and notifications.
                </p>
                @error('donation_note')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                If you don’t wish to add a donation link, go back and create a normal list. Creator Mode is designed for creators who wish to connect their Dua list to a fundraising event or activity.
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('onboarding.show', 'image') }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-900 hover:text-emerald-700">
                Go back
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl px-6 py-3.5 text-sm font-extrabold text-white transition disabled:cursor-not-allowed disabled:bg-stone-300"
                x-bind:class="canSubmit ? 'bg-emerald-900 hover:bg-emerald-800' : 'bg-stone-300'"
                x-bind:disabled="! canSubmit"
            >
                Next
            </button>
        </div>
    </form>
</x-onboarding.layout>
