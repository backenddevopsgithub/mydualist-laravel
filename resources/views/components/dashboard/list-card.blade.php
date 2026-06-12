@props([
    'duaList',
])

@php
    $shareUrl = $duaList->publicUrl();
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<article
    class="overflow-hidden rounded-2xl border border-stone-100 bg-white shadow-[0_10px_35px_rgba(15,23,42,0.06)]"
    x-data="{ copied: false, async copyUrl() { if (await window.copyToClipboard(@js($shareUrl))) { this.copied = true; setTimeout(() => this.copied = false, 1800) } } }"
>
    {{-- Header --}}
    <div class="flex items-start gap-4 px-4 pb-4 pt-5 sm:px-6 sm:pt-6">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-extrabold tracking-tight text-stone-950">{{ $duaList->title }}</h2>
                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[0.68rem] font-extrabold text-emerald-800 ring-1 ring-emerald-100">{{ $duaList->occasionLabel() }}</span>
                @if ($duaList->daysRemainingLabel())
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[0.68rem] font-extrabold text-amber-800 ring-1 ring-amber-100">{{ $duaList->daysRemainingLabel() }}</span>
                @endif
            </div>
            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-stone-500">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4.5h10M6 3.5h12A1.5 1.5 0 0 1 19.5 5v14.5A1.5 1.5 0 0 1 18 21H6A1.5 1.5 0 0 1 4.5 19.5V5A1.5 1.5 0 0 1 6 3.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M8 2.5v3M16 2.5v3M5 9.5h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Created on {{ $duaList->created_at->format('F j, Y') }}
            </p>
        </div>

    </div>

    {{-- Progress --}}
    <div class="mx-4 rounded-xl bg-stone-50/80 px-3 py-4 sm:mx-6 sm:px-4 sm:py-5">
        <div class="grid grid-cols-3 divide-x divide-stone-200">
            <div class="flex min-h-20 flex-col items-center justify-center px-2 text-center sm:px-3">
                <p class="text-2xl font-extrabold leading-none text-emerald-700">{{ $completedCount }}</p>
                <p class="mt-2 text-xs font-medium text-stone-500">Completed</p>
            </div>

            <div class="flex min-h-20 flex-col items-center justify-center px-3 text-center sm:px-5">
                <p class="text-2xl font-extrabold leading-none text-stone-950">{{ $progress }}%</p>
                <div class="relative mt-3 h-1.5 w-full max-w-sm rounded-full bg-stone-200">
                    <div
                        class="absolute inset-y-0 left-0 rounded-full bg-emerald-600 transition-all"
                        style="width: {{ $progress }}%"
                    ></div>
                    <span
                        class="absolute top-1/2 h-3 w-3 -translate-y-1/2 rounded-full bg-emerald-600 ring-2 ring-white"
                        style="left: clamp(0px, calc({{ $progress }}% - 6px), calc(100% - 12px))"
                    ></span>
                </div>
                <p class="mt-2 text-xs font-medium text-stone-500">Overall progress</p>
            </div>

            <div class="flex min-h-20 flex-col items-center justify-center px-3 text-center">
                <p class="text-2xl font-extrabold leading-none text-emerald-700">{{ $totalSubmissions }}</p>
                <p class="mt-2 text-xs font-medium text-stone-500">Total duas</p>
            </div>
        </div>
    </div>

    {{-- View submissions --}}
    <div class="px-4 py-4 sm:px-6 sm:py-5">
        <a
            href="{{ route('dashboard.lists.show', $duaList) }}"
            class="relative flex w-full items-center justify-center gap-3 rounded-xl bg-emerald-900 px-12 py-4 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800"
        >
            <span class="inline-flex items-center gap-2.5">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                View submissions
            </span>
            <svg class="absolute right-5 h-5 w-5 shrink-0 opacity-90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>

    {{-- Share link bar --}}
    <div class="px-4 pb-5 sm:px-6 sm:pb-6">
        <div class="flex items-center gap-3 rounded-xl border border-stone-200 bg-stone-50/70 p-2.5 sm:p-2">
            <div class="flex min-w-0 flex-1 items-center gap-2.5 px-2">
                <svg class="h-4 w-4 shrink-0 text-stone-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M10 13a5 5 0 0 1 7.07 0l1.41 1.41a5 5 0 0 1-7.07 7.07l-.71-.71M14 11a5 5 0 0 1-7.07 0L5.52 9.59a5 5 0 0 1 7.07-7.07l.71.71" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="min-w-0 flex-1 break-all text-sm leading-5 text-stone-700">{{ $shareUrl }}</span>
            </div>
            <button
                type="button"
                class="relative inline-flex shrink-0 items-center gap-2 rounded-lg border border-emerald-800 bg-white px-4 py-2.5 text-xs font-extrabold text-emerald-900 transition hover:bg-emerald-50"
                x-on:click="copyUrl"
            >
                <span
                    aria-live="polite"
                    x-bind:class="copied ? 'opacity-100' : 'pointer-events-none opacity-0'"
                    class="absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-stone-950 px-3 py-1.5 text-xs font-bold text-white shadow-lg transition-opacity duration-150"
                    role="status"
                >Link copied</span>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 8.25H7.5A2.25 2.25 0 0 0 5.25 10.5v9A2.25 2.25 0 0 0 7.5 21.75h9a2.25 2.25 0 0 0 2.25-2.25V18M9 15.75h7.5M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-7.5A2.25 2.25 0 0 0 3.75 5.25v7.5A2.25 2.25 0 0 0 6 15h3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Copy link</span>
            </button>
        </div>
    </div>
</article>
