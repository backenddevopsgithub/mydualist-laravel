@php
    $locked = $visibleSubmissionLimit !== null && $submission->isQuotaLocked();
    $position = ($submissions->firstItem() ?? 1) + $loop->index;
    $displayName = $locked ? 'Locked dua request' : $submission->displayName();
    $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($displayName, 0, 1));
@endphp
<article
    class="list-submission-card overflow-hidden rounded-[1.65rem] border border-stone-950/10 bg-[#fffdfb] shadow-[0_14px_45px_rgba(15,23,42,0.07)] sm:rounded-[2rem] sm:shadow-[0_22px_70px_rgba(15,23,42,0.08)]"
    data-list-submission-card
    data-submission-id="{{ $submission->id }}"
    data-status="{{ $submission->status->value }}"
    data-complete-url="{{ route('dashboard.submissions.complete', [$duaList, $submission]) }}"
    data-undo-url="{{ route('dashboard.submissions.undo', [$duaList, $submission]) }}"
    x-data="listSubmissionCard(@js([
        'status' => $submission->status->value,
        'completeUrl' => route('dashboard.submissions.complete', [$duaList, $submission]),
        'undoUrl' => route('dashboard.submissions.undo', [$duaList, $submission]),
    ]))"
>
    <div class="relative p-4 pb-5 sm:p-7 sm:pb-10">
        <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-emerald-900/20 to-transparent"></div>

        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-emerald-100 text-sm font-black text-emerald-950 ring-1 ring-emerald-950/10 sm:h-12 sm:w-12 sm:text-base">
                    {{ $initial ?: 'D' }}
                </div>
                <div class="flex min-w-0 items-center gap-2">
                    @if (! $locked && ! $submission->isPersonalDua() && in_array($submission->gender, ['male', 'female'], true))
                        <x-submissions.gender-indicator :gender="$submission->gender" />
                        <span class="shrink-0 text-stone-300" aria-hidden="true">•</span>
                    @endif
                    <h2 class="truncate text-sm font-black tracking-tight text-stone-950 sm:text-xl">{{ $displayName }}</h2>
                </div>
            </div>

            @if (! $locked)
                @if ($submission->status === App\Enums\DuaSubmissionStatus::Hidden)
                    <form method="POST" action="{{ route('dashboard.submissions.unhide', [$duaList, $submission]) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-full p-2 text-emerald-800 transition hover:bg-emerald-50" aria-label="Unhide dua">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                <a href="{{ route('dashboard.upgrade', ['product' => 'request_pack_25', 'dua_list_id' => $duaList->id]) }}" class="mt-5 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Unlock More Requests</a>
            </div>
        @else
            <p class="mt-5 text-[1.02rem] leading-8 tracking-tight sm:mt-7 sm:text-[1.45rem] sm:leading-10">{!! $submission->readableContent() !!}</p>
        @endif

        @if (! $locked && $submission->note)
            <p class="mt-4 rounded-2xl bg-stone-50 px-4 py-3 text-sm leading-6 text-stone-600 ring-1 ring-stone-100 sm:mt-5">{{ $submission->note }}</p>
        @endif

        <p class="mt-5 text-[0.62rem] font-bold uppercase tracking-[0.16em] text-stone-400 sm:mt-6 sm:text-xs">Submitted {{ $submission->created_at->diffForHumans() }}</p>
    </div>

    <div class="relative flex min-h-16 items-center justify-between gap-3 bg-emerald-950 px-4 py-3 text-white sm:min-h-20 sm:px-7 sm:py-4">
        <div class="flex flex-wrap items-center gap-2 text-[0.7rem] font-extrabold text-emerald-50 sm:gap-3 sm:text-sm">
            <span>
                Dua {{ $position }}/{{ $submissions->total() }}
                @if ($submission->isPersonalDua())
                    • Personal Dua
                @endif
            </span>
            @if (! $locked)
                <button type="button" x-on:click="reportOpen = true" class="rounded-full bg-white/10 px-2.5 py-1 text-[0.65rem] font-extrabold text-emerald-50 transition hover:bg-white/15 sm:px-3 sm:text-xs">Report</button>
            @endif
        </div>

        @if ($locked)
            <a href="{{ route('dashboard.upgrade', ['product' => 'request_pack_25', 'dua_list_id' => $duaList->id]) }}" class="inline-flex items-center justify-center rounded-full bg-lime-300 px-5 py-3 text-sm font-black text-emerald-950 shadow-[0_12px_30px_rgba(132,204,22,0.30)]">Unlock</a>
        @elseif ($submission->status === App\Enums\DuaSubmissionStatus::Hidden)
            <span class="text-sm font-extrabold text-emerald-50">Hidden</span>
        @else
            <button
                type="button"
                data-submission-toggle="complete"
                @class([
                    'submission-toggle-complete shrink-0 flex h-12 w-12 items-center justify-center rounded-full bg-lime-400 text-emerald-950 shadow-[0_14px_32px_rgba(132,204,22,0.38)] ring-4 ring-[#fffdfb] transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60 sm:absolute sm:right-4 sm:-top-5 sm:h-16 sm:w-16 sm:shadow-[0_18px_45px_rgba(132,204,22,0.45)]',
                    'hidden' => $submission->status === App\Enums\DuaSubmissionStatus::Completed,
                ])
                aria-label="Mark dua complete"
            >
                <svg class="h-7 w-7 sm:h-9 sm:w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m5 12.5 4.2 4.2L19.5 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <button
                type="button"
                data-submission-toggle="undo"
                @class([
                    'submission-toggle-undo shrink-0 flex h-12 w-12 items-center justify-center rounded-full bg-amber-300 text-amber-950 shadow-[0_14px_32px_rgba(251,191,36,0.3)] ring-4 ring-[#fffdfb] transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60 sm:absolute sm:right-4 sm:-top-5 sm:h-16 sm:w-16 sm:shadow-[0_18px_45px_rgba(251,191,36,0.35)]',
                    'hidden' => $submission->status !== App\Enums\DuaSubmissionStatus::Completed,
                ])
                aria-label="Undo completion"
            >
                <svg class="h-7 w-7 sm:h-8 sm:w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 7h7a5 5 0 1 1 0 10H6M7 7V3M7 7H3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        @endif
    </div>

    <template x-teleport="body">
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
    </template>
</article>
