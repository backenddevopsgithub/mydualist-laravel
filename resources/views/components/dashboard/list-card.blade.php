@props([
    'duaList',
])

@php
    $shareUrl = $duaList->publicUrl();
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<x-ui.card class="transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex flex-wrap items-center gap-2">
        <h2 class="text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">{{ $duaList->title }}</h2>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-800">{{ $duaList->occasionLabel() }}</span>
        @if ($duaList->daysRemainingLabel())
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold text-amber-800">{{ $duaList->daysRemainingLabel() }}</span>
        @endif
    </div>

    <div class="mt-5 grid grid-cols-3 items-center gap-3 text-center text-sm">
        <div>
            <p class="text-lg font-extrabold text-emerald-900">{{ $completedCount }}</p>
            <p class="text-xs font-semibold text-stone-500">Completed</p>
        </div>
        <div>
            <p class="text-sm font-extrabold text-stone-700">{{ $progress }}%</p>
            <div class="mt-2 h-2 rounded-full bg-stone-100">
                <div class="h-2 rounded-full bg-emerald-700" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        <div>
            <p class="text-lg font-extrabold text-stone-950">{{ $totalSubmissions }}</p>
            <p class="text-xs font-semibold text-stone-500">Total</p>
        </div>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <x-ui.button :href="route('dashboard.lists.show', $duaList)" variant="primary" class="flex-1">
            View submissions
        </x-ui.button>
        <div
            class="flex flex-1 overflow-hidden rounded-xl border border-stone-200 bg-stone-50"
            x-data="{ copied: false, copyUrl() { navigator.clipboard.writeText(@js($shareUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }).catch(() => {}) } }"
        >
            <input value="{{ $shareUrl }}" readonly class="min-w-0 flex-1 bg-transparent px-4 py-3 text-xs font-medium text-stone-700 outline-none sm:text-sm">
            <x-ui.button type="button" variant="secondary" size="sm" class="rounded-none border-0 border-l border-stone-200" x-on:click="copyUrl">
                <span x-show="! copied">Copy link</span>
                <span x-cloak x-show="copied">Copied</span>
            </x-ui.button>
        </div>
    </div>
</x-ui.card>
