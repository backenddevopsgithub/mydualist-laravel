@php
    $statuses = [
        App\Enums\DuaSubmissionStatus::Pending->value => 'Incomplete Duas',
        App\Enums\DuaSubmissionStatus::Completed->value => 'Completed Duas',
    ];
    $isViewingHidden = $currentStatus === App\Enums\DuaSubmissionStatus::Hidden->value;
@endphp

<x-dashboard.layout :user="$user" title="{{ $duaList->title }} Submissions - My Dua List">
    <main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ $duaList->isArchived() ? route('dashboard', ['tab' => 'archived']) : route('dashboard') }}" class="text-xs font-extrabold text-emerald-800 hover:text-emerald-700">Back to dashboard</a>
                <h1 class="dashboard-page-title mt-3">{{ $duaList->title }}</h1>
                <p class="mt-2 max-w-md text-xs font-medium leading-5 text-stone-600 sm:mt-3 sm:text-sm sm:leading-6">{{ $isViewingHidden ? 'Review hidden dua requests for this list.' : 'Review, complete, and manage dua requests for this list.' }}</p>
            </div>
            <div class="flex w-full flex-col gap-3 lg:w-auto" x-data="{ editListOpen: false }" x-on:keydown.escape.window="editListOpen = false">
                <div class="grid grid-cols-[1fr_auto] items-center gap-3 sm:flex sm:flex-wrap sm:items-center sm:gap-4">
                    <div class="inline-flex w-full rounded-2xl border border-emerald-950/10 bg-white p-1 shadow-sm sm:w-auto sm:p-1.5" role="radiogroup" aria-label="List status">
                        @if ($duaList->isArchived())
                            <span class="flex-1 rounded-xl bg-emerald-800 px-4 py-2.5 text-center text-xs font-extrabold text-white sm:flex-none sm:px-5 sm:text-sm">OFF</span>
                            <form method="POST" action="{{ route('dashboard.lists.restore', $duaList) }}" class="contents">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                <button type="submit" class="flex-1 rounded-xl px-4 py-2.5 text-xs font-extrabold text-stone-500 transition hover:bg-emerald-50 sm:flex-none sm:px-5 sm:text-sm">ON</button>
                            </form>
                        @else
                            <span class="flex-1 rounded-xl bg-emerald-800 px-4 py-2.5 text-center text-xs font-extrabold text-white sm:flex-none sm:px-5 sm:text-sm">ON</span>
                            <form method="POST" action="{{ route('dashboard.lists.archive', $duaList) }}" class="contents">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                <button type="submit" class="flex-1 rounded-xl px-4 py-2.5 text-xs font-extrabold text-stone-500 transition hover:bg-emerald-50 sm:flex-none sm:px-5 sm:text-sm">OFF</button>
                            </form>
                        @endif
                    </div>
                    <div class="relative shrink-0">
                        <button
                            type="button"
                            x-on:click="editListOpen = ! editListOpen"
                            x-bind:aria-expanded="editListOpen"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-950/10 bg-white text-emerald-950 shadow-sm transition hover:bg-emerald-50"
                            aria-label="List options"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.65" />
                                <circle cx="12" cy="12" r="1.65" />
                                <circle cx="12" cy="19" r="1.65" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="editListOpen"
                            x-transition.origin.top.right
                            x-on:click.outside="editListOpen = false"
                            class="edit-list-dropdown hidden lg:block"
                        >
                            @include('dashboard.lists.partials.edit-list-form')
                        </div>
                    </div>
                </div>

                <div
                    x-cloak
                    x-show="editListOpen"
                    x-transition
                    x-on:click.outside="editListOpen = false"
                    class="edit-list-dropdown lg:hidden"
                >
                    @include('dashboard.lists.partials.edit-list-form')
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        @if (request('payment') === 'success')
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                Payment successful. Your list entitlements have been updated.
            </div>
        @endif

        @if (! $hasPremium && $lockedSubmissionCount > 0)
            <section class="mt-6 rounded-[2rem] border border-amber-200 bg-amber-50 p-5 text-amber-950 sm:p-6">
                <h2 class="text-xl font-extrabold">Upgrade to unlock {{ $lockedSubmissionCount }} more duas</h2>
                <p class="mt-2 text-sm leading-6">Your free plan shows the first {{ $visibleSubmissionLimit }} submissions on each list. Add a request pack or upgrade for unlimited access.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('dashboard.upgrade', ['product' => 'request_pack_25', 'dua_list_id' => $duaList->id]) }}" class="inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Buy 25 More Requests</a>
                    <a href="{{ route('dashboard.upgrade', ['product' => 'unlimited_one_list', 'dua_list_id' => $duaList->id]) }}" class="inline-flex rounded-2xl border border-emerald-900 bg-white px-5 py-3 text-sm font-extrabold text-emerald-900">Upgrade This List</a>
                </div>
            </section>
        @endif

        <div
            x-data="listSubmissionsPage(@js([
                'currentStatus' => $currentStatus,
                'statusCounts' => $statusCounts,
                'statusTotal' => $submissions->total(),
                'listUrl' => route('dashboard.lists.show', $duaList),
            ]))"
            data-list-submissions-root
        >
        <section class="sticky top-20 z-30 -mx-4 mt-5 bg-[#fbfaf7]/95 px-4 py-3 backdrop-blur-xl sm:mx-0 sm:mt-8 sm:rounded-[1.5rem] sm:bg-white/90 sm:px-5 sm:py-4">
            @if ($isViewingHidden)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-extrabold text-emerald-950">Hidden Duas ({{ $statusCounts[App\Enums\DuaSubmissionStatus::Hidden->value] ?? 0 }})</p>
                        <p class="mt-1 text-xs font-semibold text-stone-500">Use the eye icon to restore a hidden dua.</p>
                    </div>
                    <a href="{{ route('dashboard.lists.show', ['duaList' => $duaList, 'status' => App\Enums\DuaSubmissionStatus::Pending->value]) }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/10 bg-white px-4 py-3 text-sm font-extrabold text-emerald-900 transition hover:bg-emerald-50">
                        Back to Incomplete Duas
                    </a>
                </div>
            @else
                <nav class="grid grid-cols-2 border-b border-stone-200 text-center text-xs font-extrabold sm:text-sm" aria-label="Dua status tabs">
                    @foreach ($statuses as $value => $label)
                        <button
                            type="button"
                            x-on:click="switchTab(@js($value))"
                            x-bind:class="tabClass(@js($value))"
                            x-bind:aria-current="currentStatus === @js($value) ? 'page' : false"
                            x-bind:disabled="switching"
                            class="w-full"
                        >
                            {{ $label }} (<span x-text="countFor(@js($value))">{{ $statusCounts[$value] ?? 0 }}</span>)
                        </button>
                    @endforeach
                </nav>
            @endif
        </section>

        @if (! $isViewingHidden)
            <div
                class="mt-4 sm:mt-6"
                x-data="{ personalDuaOpen: @json($errors->has('content')) }"
                x-on:keydown.escape.window="personalDuaOpen = false"
            >
                <x-ui.button
                    type="button"
                    variant="primary"
                    fullWidth
                    x-on:click="personalDuaOpen = true"
                >
                    Add Personal Dua
                </x-ui.button>

                <template x-teleport="body">
                    <div x-cloak x-show="personalDuaOpen" class="fixed inset-0 z-50 flex items-end bg-stone-950/40 p-4 backdrop-blur-sm sm:items-center sm:justify-center">
                        <form
                            method="POST"
                            action="{{ route('dashboard.lists.personal-duas.store', $duaList) }}"
                            class="w-full rounded-[2rem] bg-white p-5 shadow-2xl sm:max-w-lg sm:p-6"
                            x-on:click.outside="personalDuaOpen = false"
                        >
                            @csrf

                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-extrabold text-stone-950">Keep all your duas in one place</h3>
                                    <p class="mt-2 text-sm leading-6 text-stone-600">You can add an unlimited number of personal duas to your list. We'll combine them with duas from your network and keep everything in one place.</p>
                                </div>
                                <button type="button" x-on:click="personalDuaOpen = false" class="rounded-full bg-stone-100 px-3 py-1 text-sm font-extrabold text-stone-600">Close</button>
                            </div>

                            <div class="mt-5">
                                <x-ui.textarea
                                    name="content"
                                    rows="5"
                                    placeholder="Type your dua here..."
                                    required
                                >{{ old('content') }}</x-ui.textarea>
                            </div>

                            <x-ui.button type="submit" variant="primary" fullWidth class="mt-5">
                                Add Personal Dua
                            </x-ui.button>
                        </form>
                    </div>
                </template>
            </div>
        @endif

        <section class="mt-4 sm:mt-6">
            <div data-submissions-list>
                @include('dashboard.lists.partials.submission-cards', [
                    'submissions' => $submissions,
                    'duaList' => $duaList,
                    'visibleSubmissionLimit' => $visibleSubmissionLimit,
                ])
            </div>

            @include('dashboard.lists.partials.community-dua-section')

            <div class="pt-4 text-center">
                @if ($isViewingHidden)
                    <a href="{{ route('dashboard.lists.show', ['duaList' => $duaList, 'status' => App\Enums\DuaSubmissionStatus::Pending->value]) }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/10 bg-white px-5 py-3 text-sm font-extrabold text-emerald-900 transition hover:bg-emerald-50">
                        Back to Incomplete Duas
                    </a>
                @elseif (($statusCounts[App\Enums\DuaSubmissionStatus::Hidden->value] ?? 0) > 0)
                    <a href="{{ route('dashboard.lists.show', ['duaList' => $duaList, 'status' => App\Enums\DuaSubmissionStatus::Hidden->value]) }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/10 bg-white px-5 py-3 text-sm font-extrabold text-emerald-900 transition hover:bg-emerald-50">
                        See Hidden Duas ({{ $statusCounts[App\Enums\DuaSubmissionStatus::Hidden->value] ?? 0 }})
                    </a>
                @endif
            </div>
        </section>
        </div>
    </main>
</x-dashboard.layout>
