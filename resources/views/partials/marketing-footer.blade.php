<footer class="bg-emerald-950 text-white">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-[1.4fr_repeat(3,1fr)]">
            <div>
                <x-home.logo class="[&_img]:h-10 [&_img]:brightness-0 [&_img]:invert" />
                <p class="mt-5 max-w-xs text-sm leading-7 text-emerald-100/80">
                    Your companion for collecting, organizing and sharing duas with ease — built for Hajj, Umrah, and every moment that matters.
                </p>
            </div>

            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-wide text-emerald-200">Product</h3>
                <div class="mt-4 grid gap-2.5 text-sm text-emerald-50/90">
                    <a href="{{ route('home') }}#home" class="transition hover:text-white">Home</a>
                    <a href="{{ route('home') }}#features" class="transition hover:text-white">Features</a>
                    <a href="{{ route('home') }}#pricing" class="transition hover:text-white">Pricing</a>
                    <a href="{{ route('home') }}#how-it-works" class="transition hover:text-white">How It Works</a>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-wide text-emerald-200">Resources</h3>
                <div class="mt-4 grid gap-2.5 text-sm text-emerald-50/90">
                    <a href="{{ route('blogs.index') }}" class="transition hover:text-white">Dua Resources</a>
                    <a href="{{ route('blogs.index') }}" class="transition hover:text-white">Blog</a>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-wide text-emerald-200">Account</h3>
                <div class="mt-4 grid gap-2.5 text-sm text-emerald-50/90">
                    <a href="{{ route('login') }}" class="transition hover:text-white">Log in</a>
                    <a href="{{ route('onboarding.start') }}" class="transition hover:text-white">Create a List</a>
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-start justify-between gap-4 border-t border-emerald-800 pt-8 sm:flex-row sm:items-center">
            <p class="text-xs font-semibold text-emerald-200/70">© {{ now()->year }} My Dua List. All rights reserved.</p>
        </div>
    </div>
</footer>
