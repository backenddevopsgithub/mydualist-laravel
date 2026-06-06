<x-onboarding.layout
    step="success"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Your List is Ready!"
    subtitle="Share your list and start receiving dua requests from your loved ones."
>
    <div class="text-center">
        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-emerald-50 text-emerald-800">
            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        </div>

        <div class="mt-8 flex overflow-hidden rounded-xl border border-stone-200 bg-stone-50 text-left">
            <input value="{{ $shareUrl }}" readonly class="min-w-0 flex-1 bg-transparent px-4 py-3 text-sm text-stone-600 outline-none">
            <a href="{{ $shareUrl }}" class="flex items-center justify-center bg-emerald-800 px-4 text-white" aria-label="Open share link">
                ↗
            </a>
        </div>

        <p class="mt-7 text-sm font-bold text-stone-700">Share your list</p>
        <div class="mt-4 flex flex-wrap justify-center gap-3 text-sm">
            @foreach (['WhatsApp', 'Telegram', 'Link', 'Facebook', 'X', 'Email'] as $item)
                <span class="flex h-10 min-w-10 items-center justify-center rounded-full bg-emerald-50 px-3 font-semibold text-emerald-800 ring-1 ring-emerald-900/5">
                    {{ $item }}
                </span>
            @endforeach
        </div>

        <a href="{{ route('dashboard') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 sm:w-auto sm:min-w-72">
            Go to Dashboard
        </a>
    </div>
</x-onboarding.layout>
