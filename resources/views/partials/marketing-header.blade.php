<header
    class="sticky top-0 z-50 border-b border-emerald-950/10 bg-[#fbfaf5]/90 backdrop-blur-xl"
    x-data="{ open: false }"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
        <x-home.logo />

        <nav class="hidden items-center gap-8 text-base font-bold text-stone-700 lg:flex" aria-label="Main navigation">
            <a href="{{ route('home') }}#home" @class(['border-b-2 border-emerald-800 pb-1 text-emerald-900' => request()->routeIs('home'), 'transition hover:text-emerald-800' => ! request()->routeIs('home')])>Home</a>
            <a href="{{ route('blogs.index') }}" @class(['border-b-2 border-emerald-800 pb-1 text-emerald-900' => request()->routeIs('blogs.*'), 'transition hover:text-emerald-800' => ! request()->routeIs('blogs.*')])>Dua Resources</a>
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-950 px-5 py-2.5 text-base font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-base font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-900">
                    Login
                </a>
                <a href="{{ route('onboarding.start') }}" class="rounded-xl bg-emerald-950 px-5 py-2.5 text-base font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                    Create My List
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

    <div class="border-t border-emerald-950/10 bg-white px-5 py-4 shadow-xl shadow-emerald-950/5 lg:hidden" x-cloak x-show="open">
        <div class="mx-auto grid max-w-7xl gap-2 text-base font-bold text-stone-700">
            <a href="{{ route('home') }}#home" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Home</a>
            <a href="{{ route('blogs.index') }}" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Dua Resources</a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-950 px-4 py-3 text-center text-white">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Login</a>
                <a href="{{ route('onboarding.start') }}" class="rounded-xl bg-emerald-950 px-4 py-3 text-center text-white">Create My List</a>
            @endauth
        </div>
    </div>
</header>
