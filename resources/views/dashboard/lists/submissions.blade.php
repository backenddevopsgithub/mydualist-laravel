@php
    $statuses = [
        App\Enums\DuaSubmissionStatus::Pending->value => 'Pending',
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
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">Back to dashboard</a>
                <h1 class="mt-4 font-serif text-4xl font-bold tracking-tight text-emerald-950">{{ $duaList->title }}</h1>
                <p class="mt-3 text-sm leading-6 text-stone-600">Review, complete, hide, archive, and manage dua requests for this list.</p>
            </div>
            <a href="{{ $duaList->publicUrl() }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">
                Open Share Page
            </a>
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

        <section class="mt-8">
            <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 sm:mx-0 sm:flex-wrap sm:px-0">
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

            <form method="GET" action="{{ route('dashboard.lists.show', $duaList) }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <input name="search" value="{{ $search }}" placeholder="Search by name, email, or dua..." class="min-w-0 flex-1 rounded-2xl border border-emerald-950/10 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <button type="submit" class="rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Search</button>
            </form>
        </section>

        <section class="mt-6 space-y-4">
            @forelse ($submissions as $submission)
                <article class="rounded-[2rem] border border-emerald-950/10 bg-white p-5 shadow-[0_18px_60px_rgba(15,23,42,0.06)] sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-extrabold text-stone-950">{{ $submission->displayName() }}</h2>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-800">{{ Illuminate\Support\Str::headline($submission->status->value) }}</span>
                                @if ($submission->email)
                                    <span class="text-xs font-semibold text-stone-500">{{ $submission->email }}</span>
                                @endif
                            </div>
                            <p class="mt-4 whitespace-pre-line text-base leading-8 text-stone-700">{{ $submission->content }}</p>
                            @if ($submission->note)
                                <p class="mt-4 rounded-2xl bg-stone-50 px-4 py-3 text-sm leading-6 text-stone-600">{{ $submission->note }}</p>
                            @endif
                            <p class="mt-4 text-xs font-semibold text-stone-500">Submitted {{ $submission->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="grid shrink-0 grid-cols-2 gap-2 text-sm font-bold lg:w-72">
                            @if ($submission->status !== App\Enums\DuaSubmissionStatus::Completed)
                                <form method="POST" action="{{ route('dashboard.submissions.complete', [$duaList, $submission]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-2xl bg-emerald-900 px-4 py-3 text-white">Complete</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('dashboard.submissions.undo', [$duaList, $submission]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-2xl bg-amber-50 px-4 py-3 text-amber-800">Undo</button>
                                </form>
                            @endif

                            @if ($submission->status === App\Enums\DuaSubmissionStatus::Hidden)
                                <form method="POST" action="{{ route('dashboard.submissions.unhide', [$duaList, $submission]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-2xl bg-emerald-50 px-4 py-3 text-emerald-900">Unhide</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('dashboard.submissions.hide', [$duaList, $submission]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-2xl bg-stone-100 px-4 py-3 text-stone-700">Hide</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('dashboard.submissions.archive', [$duaList, $submission]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="w-full rounded-2xl bg-stone-100 px-4 py-3 text-stone-700">Archive</button>
                            </form>

                            <form method="POST" action="{{ route('dashboard.submissions.report', [$duaList, $submission]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="w-full rounded-2xl bg-red-50 px-4 py-3 text-red-700">Report</button>
                            </form>

                            <form method="POST" action="{{ route('dashboard.submissions.destroy', [$duaList, $submission]) }}" class="col-span-2" onsubmit="return confirm('Delete this dua request?')">
                                @csrf
                                @method('DELETE')
                                <button class="w-full rounded-2xl border border-red-100 px-4 py-3 text-red-700">Delete</button>
                            </form>
                        </div>
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
