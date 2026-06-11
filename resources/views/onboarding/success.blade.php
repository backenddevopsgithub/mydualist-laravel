@php
    $user = auth()->user();
    $firstName = $user?->first_name ?: \Illuminate\Support\Str::before($user?->name ?? 'Your', ' ');
    $occasionLabel = $duaList ? \App\Support\DuaListOccasions::label($duaList->occasion) : '';
    $displayTitle = $duaList ? "{$firstName}'s {$occasionLabel} Dua List" : 'Your Dua List';
    $shareUrl = $shareUrl ?? ($duaList ? $duaList->publicUrl() : '');
@endphp

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

        <h2 class="mt-8 text-2xl font-extrabold text-emerald-900 sm:text-3xl">{{ $displayTitle }}</h2>

        <div
            class="mt-8 flex overflow-hidden rounded-2xl border border-stone-200 bg-stone-50 text-left"
            x-data="{ copied: false, copyUrl() { navigator.clipboard.writeText(@js($shareUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }"
        >
            <input value="{{ $shareUrl }}" readonly class="min-w-0 flex-1 bg-transparent px-4 py-3.5 text-sm font-semibold text-stone-600 outline-none">
            <button type="button" x-on:click="copyUrl" class="flex min-w-[5.5rem] items-center justify-center bg-emerald-800 px-4 text-sm font-bold text-white transition hover:bg-emerald-700">
                <span x-show="! copied">Copy</span>
                <span x-cloak x-show="copied">Copied</span>
            </button>
        </div>

        <p class="mt-7 text-sm font-bold text-stone-700">Share your list</p>
        <div class="mt-4 flex flex-wrap justify-center gap-3">
            @php
                $encodedUrl = urlencode($shareUrl);
                $encodedTitle = urlencode($displayTitle);
            @endphp
            <a href="https://wa.me/?text={{ $encodedTitle }}%20{{ $encodedUrl }}" target="_blank" rel="noopener" class="flex h-11 min-w-11 items-center justify-center rounded-full bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5 transition hover:bg-emerald-100">WhatsApp</a>
            <a href="https://t.me/share/url?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener" class="flex h-11 min-w-11 items-center justify-center rounded-full bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5 transition hover:bg-emerald-100">Telegram</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener" class="flex h-11 min-w-11 items-center justify-center rounded-full bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5 transition hover:bg-emerald-100">Facebook</a>
            <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener" class="flex h-11 min-w-11 items-center justify-center rounded-full bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5 transition hover:bg-emerald-100">X</a>
            <a href="mailto:?subject={{ $encodedTitle }}&body={{ $encodedUrl }}" class="flex h-11 min-w-11 items-center justify-center rounded-full bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5 transition hover:bg-emerald-100">Email</a>
        </div>

        <a href="{{ route('dashboard') }}" class="mt-10 inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 sm:w-auto sm:min-w-72">
            Go to Dashboard
        </a>
    </div>
</x-onboarding.layout>
