<x-dashboard.layout :user="$user">
    <main class="mx-auto max-w-7xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <section class="lg:hidden">
            <p class="text-sm font-bold text-stone-500">Assalamu Alaikum,</p>
            <h1 class="mt-1 text-4xl font-extrabold tracking-tight text-stone-950">{{ $user->first_name ?: Illuminate\Support\Str::before($user->name, ' ') }}</h1>
            <p class="mt-2 text-sm font-medium text-stone-600">Manage your dua lists and track progress.</p>
        </section>

        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <section class="hidden items-end justify-between lg:flex">
            <div>
                <h1 class="font-serif text-5xl font-bold tracking-tight text-emerald-950">{{ $currentStatus === App\Models\DuaList::STATUS_ARCHIVED ? 'Archived Lists' : 'My Lists' }}</h1>
                <p class="mt-3 text-sm font-medium text-stone-600">Manage and track your dua lists.</p>
            </div>
            <x-ui.button :href="route('onboarding.start')" variant="primary" size="lg">
                Create new list
            </x-ui.button>
        </section>

        <section class="-mx-5 mt-6 flex gap-3 overflow-x-auto px-5 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 lg:grid-cols-4" aria-label="Dashboard stats">
            <x-dashboard.stat-card label="Active Lists" :value="$activeListsCount" description="View all your active lists" tone="emerald" />
            <x-dashboard.stat-card label="Archived Lists" :value="$archivedListsCount" description="Lists you've archived" tone="amber" />
            <x-dashboard.stat-card label="Total Submissions" :value="$totalSubmissionsCount" description="Across all your lists" tone="sky" />
            <x-dashboard.stat-card label="Completed Duas" :value="$completedDuasCount" description="Duas marked complete" tone="stone" />
        </section>

        <section id="lists" class="mt-8 lg:mt-10">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-emerald-950 lg:hidden">{{ $currentStatus === App\Models\DuaList::STATUS_ARCHIVED ? 'Archived Lists' : 'My Lists' }}</h2>
                    <x-ui.tabs class="lg:max-w-xl">
                        <x-ui.tab :href="route('dashboard')" :active="$currentStatus === App\Models\DuaList::STATUS_ACTIVE">Active lists</x-ui.tab>
                        <x-ui.tab :href="route('dashboard', ['tab' => 'archived'])" :active="$currentStatus === App\Models\DuaList::STATUS_ARCHIVED">Archived lists</x-ui.tab>
                    </x-ui.tabs>
                </div>

                <x-ui.button :href="route('onboarding.start')" variant="primary" size="md" class="lg:hidden">
                    Create list
                </x-ui.button>
            </div>

            <div class="mt-5 space-y-5 lg:mt-7">
                @forelse ($duaLists as $duaList)
                    <x-dashboard.list-card :dua-list="$duaList" />
                @empty
                    <x-ui.card class="border-dashed text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        @if ($currentStatus === App\Models\DuaList::STATUS_ARCHIVED)
                            <h2 class="mt-5 text-2xl font-extrabold">No archived lists yet</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Archived lists will appear here when you pause or close an active list.</p>
                            <x-ui.button :href="route('dashboard')" variant="primary" class="mt-6">View active lists</x-ui.button>
                        @else
                            <h2 class="mt-5 text-2xl font-extrabold">Create your first dua list</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Start a new list and invite others to share in the blessings.</p>
                            <x-ui.button :href="route('onboarding.start')" variant="primary" class="mt-6">Create new list</x-ui.button>
                        @endif
                    </x-ui.card>
                @endforelse
            </div>

            @if ($duaLists->hasPages())
                <div class="mt-8">
                    {{ $duaLists->links() }}
                </div>
            @endif

        </section>
    </main>
</x-dashboard.layout>
