<div
    data-list-submissions-scroll
    data-next-page-url="{{ $nextSubmissionPageUrl }}"
    data-status-total="{{ $submissions->total() }}"
    data-current-status="{{ $currentStatus }}"
>
    <div class="space-y-3 sm:space-y-4" data-submissions-items>
        @if ($submissions->isEmpty())
            <div data-submissions-empty class="rounded-[2rem] border border-dashed border-emerald-950/15 bg-white p-10 text-center shadow-sm">
                <h2 class="text-2xl font-extrabold">No dua requests here yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Share your list link or switch filters to review another status.</p>
                <button type="button" x-data="{ copied: false, async copyUrl() { if (await window.copyToClipboard(@js($duaList->publicUrl()))) { this.copied = true; setTimeout(() => this.copied = false, 1800) } } }" x-on:click="copyUrl" class="relative mt-6 rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">
                    <span
                        aria-live="polite"
                        x-bind:class="copied ? 'opacity-100' : 'pointer-events-none opacity-0'"
                        class="absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-stone-950 px-3 py-1.5 text-xs font-bold text-white shadow-lg transition-opacity duration-150"
                        role="status"
                    >Link copied</span>
                    <span>Copy Share Link</span>
                </button>
            </div>
        @else
            @include('dashboard.lists.partials.submission-card-items', [
                'submissions' => $submissions,
                'duaList' => $duaList,
                'visibleSubmissionLimit' => $visibleSubmissionLimit,
            ])
        @endif
    </div>

    <div data-submissions-scroll-loading class="mt-8 hidden text-center" aria-live="polite" aria-busy="true">
        <div class="inline-flex items-center gap-3 rounded-2xl bg-white px-5 py-3 text-sm font-bold text-stone-600 shadow-sm ring-1 ring-stone-200">
            <svg class="h-5 w-5 animate-spin text-emerald-800" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            Loading more duas...
        </div>
    </div>

    <div data-submissions-scroll-end @class(['mt-8 text-center text-sm font-semibold text-stone-500', 'hidden' => $submissions->hasMorePages()]) aria-live="polite">
        @if ($submissions->total() > 0)
            <p>All {{ number_format($submissions->total()) }} {{ $currentStatus === App\Enums\DuaSubmissionStatus::Completed->value ? 'completed' : 'incomplete' }} duas loaded.</p>
        @endif
    </div>

    <div data-submissions-scroll-error class="mt-8 hidden text-center" aria-live="assertive">
        <p class="text-sm font-semibold text-red-700">Unable to load more duas.</p>
        <button type="button" data-submissions-scroll-retry class="mt-4 rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Retry</button>
    </div>

    <div data-submissions-scroll-sentinel class="h-4 w-full shrink-0" aria-hidden="true"></div>
</div>
