@php
    $sidebarUser = auth()->user();
    $hasPremium = $sidebarUser
        ? app(App\Domains\Billing\Services\UserEntitlementService::class)->hasPremium($sidebarUser)
        : false;
@endphp

<aside class="hidden min-h-screen border-r border-emerald-950/10 bg-[#f3f6f0] px-5 py-6 lg:block">
    <x-home.logo />

    <nav class="mt-10 space-y-2 text-sm font-bold text-stone-700" aria-label="Sidebar navigation">
        <a href="{{ route('dashboard') }}" @class([
            'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
            'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard'),
        ])>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-emerald-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            My Lists
        </a>
        <a href="{{ route('dashboard.archived') }}" @class([
            'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
            'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.archived'),
        ])>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-emerald-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 7.5A2.5 2.5 0 0 1 7.5 5h9A2.5 2.5 0 0 1 19 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 16.5v-9Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
            </span>
            Archived Lists
        </a>
    </nav>

    <div class="mt-8 border-t border-emerald-950/10 pt-8">
        <p class="px-4 text-xs font-bold uppercase tracking-[0.18em] text-stone-400">Account</p>
        <nav class="mt-3 space-y-2 text-sm font-bold text-stone-700" aria-label="Account navigation">
            <a href="{{ route('dashboard.profile') }}" @class([
                'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.profile'),
            ])>Profile</a>
            <a href="{{ route('dashboard.upgrade') }}" @class([
                'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.upgrade'),
            ])>Upgrade Plan</a>
            <a href="{{ route('dashboard.submissions') }}" @class([
                'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.submissions'),
            ])>My Submissions</a>
        </nav>
    </div>

    <div class="mt-8 border-t border-emerald-950/10 pt-8">
        <p class="px-4 text-xs font-bold uppercase tracking-[0.18em] text-stone-400">Support</p>
        <nav class="mt-3 space-y-2 text-sm font-bold text-stone-700" aria-label="Support navigation">
            <a href="{{ route('dashboard.support') }}" @class([
                'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-white hover:text-emerald-950',
                'bg-emerald-900/10 text-emerald-950' => request()->routeIs('dashboard.support'),
            ])>Help & Support</a>
        </nav>
    </div>

    <div class="mt-10 rounded-3xl {{ $hasPremium ? 'bg-white text-emerald-950 ring-1 ring-emerald-900/10' : 'bg-emerald-900 text-white' }} p-5 shadow-xl shadow-emerald-950/10">
        @if ($hasPremium)
            <p class="text-base font-extrabold">Premium Active</p>
            <p class="mt-2 text-sm leading-6 text-stone-600">Unlimited lists and submissions are unlocked.</p>
        @else
            <p class="text-base font-extrabold">Upgrade to Premium</p>
            <p class="mt-2 text-sm leading-6 text-emerald-50/85">Unlock unlimited dua requests and powerful features.</p>
            <a href="{{ route('dashboard.upgrade') }}" class="mt-5 inline-flex rounded-xl bg-white px-4 py-2 text-sm font-bold text-emerald-950">Upgrade Now</a>
        @endif
    </div>
</aside>
