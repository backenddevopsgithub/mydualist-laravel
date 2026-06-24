<header
    class="sticky top-0 z-50 border-b border-emerald-950/5 bg-white/95 backdrop-blur-xl"
    x-data="{ open: false }"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
        <x-home.logo />

        <p class="hidden text-sm font-bold text-emerald-800 lg:block">Dua Resources</p>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full border-2 border-emerald-800 px-5 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-bold text-stone-700 transition hover:text-emerald-800">
                    Log in
                </a>
                <a href="{{ route('onboarding.start') }}" class="rounded-full bg-emerald-800 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    Get Started
                </a>
            @endauth
        </div>

        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-950/10 bg-white text-emerald-950 shadow-sm lg:hidden"
            aria-label="Open navigation"
            x-on:click="open = ! open"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <div class="border-t border-emerald-950/5 bg-white px-5 py-4 shadow-xl lg:hidden" x-cloak x-show="open">
        <div class="mx-auto grid max-w-7xl gap-2 text-sm font-bold text-stone-700">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full border-2 border-emerald-800 px-4 py-3 text-center text-emerald-800">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Log in</a>
                <a href="{{ route('onboarding.start') }}" class="rounded-full bg-emerald-800 px-4 py-3 text-center text-white">Get Started</a>
            @endauth
        </div>
    </div>
</header>
