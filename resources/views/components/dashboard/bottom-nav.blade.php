<nav class="fixed inset-x-4 bottom-4 z-50 rounded-[1.75rem] border border-emerald-950/10 bg-white/95 p-2 shadow-[0_24px_80px_rgba(15,23,42,0.16)] backdrop-blur-xl lg:hidden" aria-label="Mobile dashboard navigation">
    <div class="grid grid-cols-4 items-center gap-1 text-xs font-bold text-stone-600">
        <a href="{{ route('dashboard') }}" @class([
            'flex flex-col items-center gap-1 rounded-2xl px-2 py-3 transition active:bg-emerald-50',
            'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard', 'dashboard.archived'),
        ])>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 11.5 12 5l8 6.5V19a1 1 0 0 1-1 1h-5v-5h-4v5H5a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
            Dashboard
        </a>
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 rounded-2xl px-2 py-3 transition active:bg-emerald-50">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            My Lists
        </a>
        <a href="{{ route('onboarding.start') }}" class="relative flex flex-col items-center gap-1 rounded-2xl px-2 py-3 transition active:bg-emerald-50">
            <span class="-mt-8 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-900 text-3xl leading-none text-white shadow-xl shadow-emerald-950/20">+</span>
            Create
        </a>
        <a href="{{ route('dashboard.profile') }}" @class([
            'flex flex-col items-center gap-1 rounded-2xl px-2 py-3 transition active:bg-emerald-50',
            'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard.profile'),
        ])>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            Profile
        </a>
    </div>
</nav>
