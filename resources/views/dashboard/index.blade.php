<x-dashboard.layout :user="$user">
    <main class="mx-auto max-w-[92rem] px-5 py-7 sm:px-6 lg:px-16 lg:py-12">
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <section class="flex flex-row items-start justify-between gap-4">
            <div>
                <h1 class="dashboard-page-title">
                    {{ $currentStatus === App\Models\DuaList::STATUS_ARCHIVED ? 'Archived Lists' : 'My Lists' }}
                </h1>
                <p class="mt-3 text-xs font-medium text-stone-600">Manage and track your dua lists.</p>
            </div>

            <a
                href="{{ route('onboarding.start') }}"
                class="mt-2 inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-900 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 sm:mt-3"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Create new list
            </a>
        </section>

        <section class="mt-7 hidden grid-cols-2 gap-3 sm:grid sm:gap-4 xl:grid-cols-4" aria-label="Dashboard stats">
            <x-dashboard.stat-card label="Active Lists" :value="$activeListsCount" description="View all your active lists" tone="emerald" />
            <x-dashboard.stat-card label="Archived Lists" :value="$archivedListsCount" description="Lists you've archived" tone="amber" />
            <x-dashboard.stat-card label="Total Submissions" :value="$totalSubmissionsCount" description="Across all your lists" tone="sky" />
            <x-dashboard.stat-card label="Completed Duas" :value="$completedDuasCount" description="Duas marked complete" tone="stone" />
        </section>

        <section id="lists" class="mt-8 lg:mt-9">
            <nav class="grid grid-cols-2 border-b border-stone-200 text-center text-sm font-bold" aria-label="List status tabs">
                <a
                    href="{{ route('dashboard') }}"
                    @class([
                        '-mb-px border-b-2 pb-3 transition',
                        'border-emerald-950 text-emerald-950' => $currentStatus === App\Models\DuaList::STATUS_ACTIVE,
                        'border-transparent text-stone-400 hover:text-stone-700' => $currentStatus !== App\Models\DuaList::STATUS_ACTIVE,
                    ])
                >
                    Active Lists
                </a>
                <a
                    href="{{ route('dashboard', ['tab' => 'archived']) }}"
                    @class([
                        '-mb-px border-b-2 pb-3 transition',
                        'border-emerald-950 text-emerald-950' => $currentStatus === App\Models\DuaList::STATUS_ARCHIVED,
                        'border-transparent text-stone-400 hover:text-stone-700' => $currentStatus !== App\Models\DuaList::STATUS_ARCHIVED,
                    ])
                >
                    Archived Lists
                </a>
            </nav>
        </section>

        <section class="mt-5 space-y-5 lg:mt-6">
            @forelse ($duaLists as $duaList)
                <x-dashboard.list-card :dua-list="$duaList" />
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-10 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    @if ($currentStatus === App\Models\DuaList::STATUS_ARCHIVED)
                        <h2 class="mt-5 text-2xl font-bold text-stone-950">No archived lists yet</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Archived lists will appear here when you pause or close an active list.</p>
                        <a href="{{ route('dashboard') }}" class="mt-6 inline-flex rounded-xl bg-emerald-900 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">View active lists</a>
                    @else
                        <h2 class="mt-5 text-2xl font-bold text-stone-950">Create your first dua list</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Start a new list and invite others to share in the blessings.</p>
                        <a href="{{ route('onboarding.start') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-900 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Create new list
                        </a>
                    @endif
                </div>
            @endforelse

            @if ($duaLists->hasPages())
                <div class="pt-4">
                    {{ $duaLists->links() }}
                </div>
            @endif
        </section>
    </main>
</x-dashboard.layout>
