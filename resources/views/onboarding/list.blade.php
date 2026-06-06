<x-onboarding.layout
    step="list"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Create Your List"
    subtitle="Let’s start with the basics. Give your list a meaningful name."
>
    <form method="POST" action="{{ route('onboarding.store', 'list') }}">
        @csrf

        <div class="space-y-6">
            <x-onboarding.input
                name="title"
                label="List Name"
                placeholder="e.g. Hajj 2027"
                :value="data_get($state, 'list.title')"
                required
                autofocus
            />

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                Choose a simple and meaningful name that your friends and family will understand.
            </div>
        </div>

        <x-onboarding.actions back="account" />
    </form>
</x-onboarding.layout>
