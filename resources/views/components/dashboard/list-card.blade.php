@props([
    'duaList',
])

@php
    $shareUrl = $duaList->publicUrl();
    $coverImageUrl = $duaList->coverImageUrl();
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<article
    class="rounded-[2rem] border border-emerald-950/10 bg-white p-4 shadow-[0_24px_80px_rgba(15,23,42,0.07)] transition hover:-translate-y-0.5 hover:shadow-[0_30px_100px_rgba(15,23,42,0.09)] sm:p-6"
    x-data="{ copied: false, copyUrl() { navigator.clipboard?.writeText(@js($shareUrl)); this.copied = true; setTimeout(() => this.copied = false, 1800) }, share() { if (navigator.share) { navigator.share({ title: @js($duaList->title), url: @js($shareUrl) }) } else { this.copyUrl() } } }"
>
    <div class="grid gap-5 lg:grid-cols-[1fr_20rem] lg:items-center">
        <div class="flex gap-4 sm:gap-5">
            <div class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-emerald-50 sm:h-32 sm:w-32">
                @if ($coverImageUrl)
                    <img src="{{ $coverImageUrl }}" alt="{{ $duaList->title }} cover image" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_35%_20%,rgba(245,158,11,0.28),transparent_28%),linear-gradient(135deg,#064e3b,#f7f0dc)] text-white">
                        <svg class="h-10 w-10 opacity-90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-xl font-extrabold tracking-tight text-stone-950 sm:text-2xl">{{ $duaList->title }}</h2>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-800">{{ $duaList->occasionLabel() }}</span>
                    @if ($duaList->daysRemainingLabel())
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold text-amber-800">{{ $duaList->daysRemainingLabel() }}</span>
                    @endif
                </div>

                <p class="mt-3 line-clamp-2 max-w-xl text-sm leading-6 text-stone-600">
                    Share this list with loved ones so they can submit duas for {{ $duaList->occasionLabel() }}.
                </p>

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
            </div>
        </div>

        <div class="space-y-3">
            <a href="{{ route('dashboard.submissions') }}" class="flex w-full items-center justify-center rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-extrabold text-emerald-950 transition hover:bg-emerald-100">
                View Submissions
            </a>

            <div class="flex overflow-hidden rounded-2xl border border-emerald-950/10 bg-emerald-50/50">
                <input value="{{ parse_url($shareUrl, PHP_URL_HOST).'/'.$duaList->slug }}" readonly class="min-w-0 flex-1 bg-transparent px-4 py-3 text-sm font-bold text-emerald-950 outline-none">
                <button type="button" x-on:click="copyUrl" class="flex w-14 items-center justify-center bg-emerald-800 text-white transition hover:bg-emerald-700" aria-label="Copy share link">
                    <span x-show="! copied">Copy</span>
                    <span x-cloak x-show="copied">Done</span>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-3 overflow-hidden rounded-2xl border border-emerald-950/10 text-sm font-bold text-emerald-950 sm:grid-cols-6">
        <button type="button" x-on:click="share" class="px-4 py-4 transition hover:bg-emerald-50 active:bg-emerald-100">Share</button>
        <a href="{{ $shareUrl }}" class="px-4 py-4 text-center transition hover:bg-emerald-50 active:bg-emerald-100">Open</a>
        <button type="button" x-on:click="copyUrl" class="px-4 py-4 transition hover:bg-emerald-50 active:bg-emerald-100">Copy</button>
        <a href="{{ route('dashboard.lists.edit', $duaList) }}" class="hidden px-4 py-4 text-center transition hover:bg-emerald-50 active:bg-emerald-100 sm:block">Edit</a>

        @if ($duaList->isArchived())
            <form method="POST" action="{{ route('dashboard.lists.restore', $duaList) }}" class="hidden sm:block">
                @csrf
                @method('PATCH')
                <button type="submit" class="h-full w-full px-4 py-4 transition hover:bg-emerald-50 active:bg-emerald-100">Restore</button>
            </form>
        @else
            <form method="POST" action="{{ route('dashboard.lists.archive', $duaList) }}" class="hidden sm:block">
                @csrf
                @method('PATCH')
                <button type="submit" class="h-full w-full px-4 py-4 transition hover:bg-emerald-50 active:bg-emerald-100">Archive</button>
            </form>
        @endif

        <form method="POST" action="{{ route('dashboard.lists.destroy', $duaList) }}" class="hidden sm:block" onsubmit="return confirm('Delete this list? This will move it out of your dashboard.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="h-full w-full px-4 py-4 text-red-600 transition hover:bg-red-50 active:bg-red-100">Delete</button>
        </form>
    </div>

    <div class="mt-3 grid grid-cols-3 gap-2 text-sm font-bold text-emerald-950 sm:hidden">
        <a href="{{ route('dashboard.lists.edit', $duaList) }}" class="rounded-2xl border border-emerald-950/10 px-4 py-3 text-center">Edit</a>
        @if ($duaList->isArchived())
            <form method="POST" action="{{ route('dashboard.lists.restore', $duaList) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full rounded-2xl border border-emerald-950/10 px-4 py-3">Restore</button>
            </form>
        @else
            <form method="POST" action="{{ route('dashboard.lists.archive', $duaList) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full rounded-2xl border border-emerald-950/10 px-4 py-3">Archive</button>
            </form>
        @endif
        <form method="POST" action="{{ route('dashboard.lists.destroy', $duaList) }}" onsubmit="return confirm('Delete this list? This will move it out of your dashboard.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full rounded-2xl border border-red-100 px-4 py-3 text-red-600">Delete</button>
        </form>
    </div>
</article>
