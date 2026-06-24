@php
    $sidebarUser = auth()->user();
    $hasPremium = $sidebarUser
        ? app(App\Domains\Billing\Services\UserEntitlementService::class)->hasPremium($sidebarUser)
        : false;
    $sidebarLists = $sidebarLists ?? collect();
    $currentListId = request()->route('duaList')?->id;
@endphp

<aside class="hidden min-h-screen border-r border-stone-200 bg-[#f3f6f0] px-5 py-6 lg:block">
    <x-home.logo />

    <div class="mt-10">
        <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-400">My Lists</p>
        <nav class="mt-3 space-y-1 text-xs font-bold text-stone-800" aria-label="My Lists navigation">
            <a href="{{ route('dashboard') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-100/70 text-emerald-950' => request()->routeIs('dashboard', 'dashboard.archived') && ! request()->routeIs('dashboard.lists.*'),
            ])>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-emerald-900">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                All Lists
            </a>

            @forelse ($sidebarLists as $list)
                <a
                    href="{{ route('dashboard.lists.show', $list) }}"
                    @class([
                        'block truncate rounded-xl px-3 py-2.5 pl-12 transition hover:bg-white hover:text-emerald-950',
                        'bg-emerald-100/70 text-emerald-950' => request()->routeIs('dashboard.lists.show') && $currentListId === $list->id,
                    ])
                >
                    {{ $list->title }}
                </a>
            @empty
                <p class="px-3 py-2 text-xs font-medium text-stone-500">No lists found</p>
            @endforelse
        </nav>
    </div>

    <div class="mt-7 border-t border-stone-200 pt-7">
        <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-400">Account</p>
        <nav class="mt-3 space-y-2 text-xs font-bold text-stone-800" aria-label="Account navigation">
            <a href="{{ route('dashboard.profile') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.profile'),
            ])>Profile</a>
            <a href="{{ route('dashboard.upgrade') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.upgrade'),
            ])>Upgrade Plan</a>
            <a href="{{ route('dashboard.purchases') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.purchases'),
            ])>Purchase History</a>
            <a href="{{ route('dashboard.submissions') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.submissions'),
            ])>My Submissions</a>
        </nav>
    </div>

    <div class="mt-7 border-t border-stone-200 pt-7">
        <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-400">Support</p>
        <nav class="mt-3 space-y-2 text-xs font-bold text-stone-800" aria-label="Support navigation">
            <a href="{{ route('dashboard.support') }}" @class([
                'flex items-center gap-3 rounded-xl px-3 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.support'),
            ])>Help & Support</a>
            <x-dashboard.logout-form />
        </nav>
    </div>

    <div class="mt-10 rounded-2xl {{ $hasPremium ? 'bg-white text-emerald-950 ring-1 ring-emerald-900/10' : 'bg-emerald-900 text-white' }} p-5 shadow-xl shadow-emerald-950/10">
        @if ($hasPremium)
            <p class="text-sm font-extrabold">Premium Active</p>
            <p class="mt-2 text-xs leading-5 text-stone-600">Unlimited lists and submissions are unlocked.</p>
        @else
            <p class="text-sm font-extrabold">Upgrade to Premium</p>
            <p class="mt-2 text-xs leading-5 text-emerald-50/85">You're on the free plan. Upgrade to unlock more features and receive unlimited dua requests.</p>
            <a href="{{ route('dashboard.upgrade') }}" class="mt-5 inline-flex rounded-lg bg-white px-4 py-2 text-xs font-bold text-emerald-950">Upgrade Now</a>
        @endif
    </div>
</aside>
