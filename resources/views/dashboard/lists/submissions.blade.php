@php
    $statuses = [
        App\Enums\DuaSubmissionStatus::Pending->value => 'Incomplete Duas',
        App\Enums\DuaSubmissionStatus::Completed->value => 'Completed',
        App\Enums\DuaSubmissionStatus::Hidden->value => 'Hidden',
        App\Enums\DuaSubmissionStatus::Archived->value => 'Archived',
        App\Enums\DuaSubmissionStatus::Reported->value => 'Reported',
    ];

    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<x-dashboard.layout :user="$user" title="{{ $duaList->title }} Submissions - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ $duaList->isArchived() ? route('dashboard.archived') : route('dashboard') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">Back to dashboard</a>
                <h1 class="mt-4 font-serif text-4xl font-bold tracking-tight text-emerald-950">{{ $duaList->title }}</h1>
                <p class="mt-3 text-sm leading-6 text-stone-600">Review, complete, hide, archive, and manage dua requests for this list.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($duaList->isArchived())
                    <form method="POST" action="{{ route('dashboard.lists.restore', $duaList) }}">
                        @csrf
                        @method('PATCH')
                        <button class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Status: OFF - Turn ON</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('dashboard.lists.archive', $duaList) }}">
                        @csrf
                        @method('PATCH')
                        <button class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-950 ring-1 ring-emerald-900/10 transition hover:bg-emerald-100">Status: ON - Turn OFF</button>
                    </form>
                @endif
                <a href="{{ $duaList->publicUrl() }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">
                    Open Share Page
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <section class="mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)]">
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <p class="text-3xl font-extrabold text-stone-950">{{ $totalSubmissions }}</p>
                    <p class="mt-1 text-sm font-bold text-stone-600">Total requests</p>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-emerald-900">{{ $completedCount }}</p>
                    <p class="mt-1 text-sm font-bold text-stone-600">Completed</p>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-stone-950">{{ $progress }}%</p>
                    <div class="mt-3 h-2 rounded-full bg-stone-100">
                        <div class="h-2 rounded-full bg-emerald-700" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        @if (! $hasPremium && $lockedSubmissionCount > 0)
            <section class="mt-6 rounded-[2rem] border border-amber-200 bg-amber-50 p-5 text-amber-950 sm:p-6">
                <h2 class="text-xl font-extrabold">Upgrade to unlock {{ $lockedSubmissionCount }} more duas</h2>
                <p class="mt-2 text-sm leading-6">Your free plan shows the first {{ $visibleSubmissionLimit }} submissions on each list. Upgrade once to unlock every current and future dua.</p>
                <a href="{{ route('dashboard.upgrade') }}" class="mt-5 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Upgrade to Premium</a>
            </section>
        @endif

        <section class="sticky top-20 z-30 -mx-5 mt-8 border-y border-emerald-950/10 bg-[#fbfaf7]/95 px-5 py-4 backdrop-blur-xl sm:mx-0 sm:rounded-[1.5rem] sm:border sm:bg-white/90">
            <div class="flex gap-2 overflow-x-auto pb-2 sm:flex-wrap sm:pb-0">
                @foreach ($statuses as $value => $label)
                    <a href="{{ route('dashboard.lists.show', ['duaList' => $duaList, 'status' => $value, 'search' => $search]) }}" @class([
                        'shrink-0 rounded-2xl px-4 py-3 text-sm font-extrabold transition',
                        'bg-emerald-900 text-white' => $currentStatus === $value,
                        'bg-white text-stone-700 ring-1 ring-emerald-950/10 hover:bg-emerald-50' => $currentStatus !== $value,
                    ])>
                        {{ $label }} ({{ $statusCounts[$value] ?? 0 }})
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('dashboard.lists.show', $duaList) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <input name="search" value="{{ $search }}" placeholder="Search by name, email, or dua..." class="min-w-0 flex-1 rounded-2xl border border-emerald-950/10 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <button type="submit" class="rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Search</button>
            </form>
        </section>

        <section class="mt-6 space-y-4">
            @forelse ($submissions as $submission)
                @php
                    $locked = ! in_array($submission->id, $visibleSubmissionIds, true);
                    $position = ($submissions->firstItem() ?? 1) + $loop->index;
                    $displayName = $locked ? 'Locked dua request' : $submission->displayName();
                    $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($displayName, 0, 1));
                @endphp
                <article class="group overflow-hidden rounded-[2rem] border border-stone-950/10 bg-[#fffdfb] shadow-[0_22px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_30px_100px_rgba(15,23,42,0.12)]" x-data="{ reportOpen: false, reason: '' }">
                    <div class="relative p-5 pb-8 sm:p-7 sm:pb-10">
                        <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-emerald-900/20 to-transparent"></div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-emerald-100 text-base font-black text-emerald-950 ring-1 ring-emerald-950/10">
                                    {{ $initial ?: 'D' }}
                                </div>
                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-black tracking-tight text-stone-950 sm:text-xl">{{ $displayName }}</h2>
                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[0.7rem] font-black uppercase tracking-[0.12em] text-emerald-800">
                                            {{ $submission->status === App\Enums\DuaSubmissionStatus::Pending ? 'Incomplete Duas' : Illuminate\Support\Str::headline($submission->status->value) }}
                                        </span>
                                        @if (! $locked && $submission->email)
                                            <span class="truncate text-xs font-semibold text-stone-500">{{ $submission->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if (! $locked)
                                @if ($submission->status === App\Enums\DuaSubmissionStatus::Hidden)
                                    <form method="POST" action="{{ route('dashboard.submissions.unhide', [$duaList, $submission]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-full p-2 text-emerald-800 transition hover:bg-emerald-50" aria-label="Unhide dua">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('dashboard.submissions.hide', [$duaList, $submission]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-full p-2 text-red-600 transition hover:bg-red-50" aria-label="Hide dua">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m4 4 16 16M9.5 5.5A8.7 8.7 0 0 1 12 5c5.5 0 9 7 9 7a14.5 14.5 0 0 1-2.1 3.1M6.2 6.8C4.1 8.3 3 12 3 12s3.5 7 9 7c1.4 0 2.7-.4 3.8-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>

                        @if ($locked)
                            <div class="mt-7 rounded-3xl bg-stone-50 p-5 ring-1 ring-stone-200">
                                <p class="select-none blur-sm text-lg font-black leading-9 tracking-tight text-stone-500">This dua request is locked on the free plan. Upgrade to read and manage it.</p>
                                <a href="{{ route('dashboard.upgrade') }}" class="mt-5 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Unlock Premium</a>
                            </div>
                        @else
                            <p class="mt-7 whitespace-pre-line text-[1.2rem] font-black leading-9 tracking-tight text-stone-950 sm:text-[1.45rem] sm:leading-10">{{ $submission->content }}</p>
                        @endif

                        @if (! $locked && $submission->note)
                            <p class="mt-5 rounded-2xl bg-stone-50 px-4 py-3 text-sm leading-6 text-stone-600 ring-1 ring-stone-100">{{ $submission->note }}</p>
                        @endif

                        <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-stone-400">Submitted {{ $submission->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="relative flex min-h-20 flex-col gap-4 bg-emerald-950 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-7">
                        <div class="flex flex-wrap items-center gap-3 text-sm font-bold text-emerald-50">
                            <span>Dua {{ $position }}/{{ $submissions->total() }}</span>
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-200"></span>
                            <span>Personal Dua</span>
                            @if (! $locked)
                                <button type="button" x-on:click="reportOpen = true" class="rounded-full bg-white/10 px-3 py-1 text-xs font-extrabold text-emerald-50 transition hover:bg-white/15">Report</button>
                                <form method="POST" action="{{ route('dashboard.submissions.archive', [$duaList, $submission]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-full bg-white/10 px-3 py-1 text-xs font-extrabold text-emerald-50 transition hover:bg-white/15">Archive</button>
                                </form>
                            @endif
                        </div>

                        @if ($locked)
                            <a href="{{ route('dashboard.upgrade') }}" class="inline-flex items-center justify-center rounded-full bg-lime-300 px-5 py-3 text-sm font-black text-emerald-950 shadow-[0_12px_30px_rgba(132,204,22,0.30)]">Unlock</a>
                        @elseif ($submission->status !== App\Enums\DuaSubmissionStatus::Completed)
                            <form method="POST" action="{{ route('dashboard.submissions.complete', [$duaList, $submission]) }}" class="sm:absolute sm:-right-1 sm:-top-5">
                                @csrf
                                @method('PATCH')
                                <button class="flex h-16 w-16 items-center justify-center rounded-full bg-lime-400 text-emerald-950 shadow-[0_18px_45px_rgba(132,204,22,0.45)] ring-4 ring-[#fffdfb] transition hover:scale-105" aria-label="Mark dua complete">
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m5 12.5 4.2 4.2L19.5 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('dashboard.submissions.undo', [$duaList, $submission]) }}" class="sm:absolute sm:-right-1 sm:-top-5">
                                @csrf
                                @method('PATCH')
                                <button class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-300 text-amber-950 shadow-[0_18px_45px_rgba(251,191,36,0.35)] ring-4 ring-[#fffdfb] transition hover:scale-105" aria-label="Undo completion">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 7h7a5 5 0 1 1 0 10H6M7 7V3M7 7H3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>

                    <div x-cloak x-show="reportOpen" class="fixed inset-0 z-50 flex items-end bg-stone-950/40 p-4 backdrop-blur-sm sm:items-center sm:justify-center">
                        <form method="POST" action="{{ route('dashboard.submissions.report', [$duaList, $submission]) }}" class="w-full rounded-[2rem] bg-white p-5 shadow-2xl sm:max-w-lg sm:p-6" x-on:click.outside="reportOpen = false">
                            @csrf
                            @method('PATCH')
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-extrabold text-stone-950">Report Dua</h3>
                                    <p class="mt-1 text-sm leading-6 text-stone-600">Choose why this dua should be reviewed.</p>
                                </div>
                                <button type="button" x-on:click="reportOpen = false" class="rounded-full bg-stone-100 px-3 py-1 text-sm font-extrabold text-stone-600">Close</button>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ([
                                    'spam' => 'Spam',
                                    'offensive' => 'Offensive content',
                                    'duplicate' => 'Duplicate',
                                    'irrelevant' => 'Irrelevant',
                                    'other' => 'Other',
                                ] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-stone-200 px-4 py-3 text-sm font-bold text-stone-700">
                                        <input type="radio" name="report_reason" value="{{ $value }}" x-model="reason" class="text-emerald-800 focus:ring-emerald-700" required>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-4" x-show="reason === 'other'">
                                <label class="block text-sm font-bold text-stone-900">Tell us more</label>
                                <textarea name="report_note" rows="4" class="mt-2 block w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100"></textarea>
                            </div>

                            <button class="mt-5 w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-extrabold text-white">Submit Report</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-emerald-950/15 bg-white p-10 text-center shadow-sm">
                    <h2 class="text-2xl font-extrabold">No dua requests here yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Share your list link or switch filters to review another status.</p>
                    <button type="button" x-data="{ copied: false }" x-on:click="navigator.clipboard?.writeText(@js($duaList->publicUrl())); copied = true; setTimeout(() => copied = false, 1800)" class="mt-6 rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">
                        <span x-show="! copied">Copy Share Link</span>
                        <span x-cloak x-show="copied">Copied</span>
                    </button>
                </div>
            @endforelse

            @if ($submissions->hasPages())
                <div class="mt-8">
                    {{ $submissions->links() }}
                </div>
            @endif
        </section>
    </main>
</x-dashboard.layout>
