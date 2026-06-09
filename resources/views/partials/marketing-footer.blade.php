<footer class="px-5 pb-10 pt-4 sm:px-6 lg:px-8">
    <div class="mx-auto grid max-w-7xl gap-8 border-t border-emerald-950/10 pt-8 md:grid-cols-[1.3fr_repeat(3,1fr)]">
        <div>
            <x-home.logo />
            <p class="mt-4 max-w-xs text-base leading-7 text-stone-600">Your companion for collecting, organizing and sharing duas with ease.</p>
            <p class="mt-4 text-sm font-semibold text-stone-500">© {{ now()->year }} My Dua List. All rights reserved.</p>
        </div>
        <div>
            <h3 class="text-base font-extrabold">Product</h3>
            <div class="mt-3 grid gap-2 text-base text-stone-600">
                <a href="{{ route('home') }}#features">Features</a>
                <a href="{{ route('home') }}#pricing">Pricing</a>
                <a href="{{ route('home') }}#how-it-works">How It Works</a>
            </div>
        </div>
        <div>
            <h3 class="text-base font-extrabold">Resources</h3>
            <div class="mt-3 grid gap-2 text-base text-stone-600">
                <a href="{{ route('blogs.index') }}">Dua Resources</a>
            </div>
        </div>
        <div>
            <h3 class="text-base font-extrabold">Company</h3>
            <div class="mt-3 grid gap-2 text-base text-stone-600">
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('onboarding.start') }}">Create List</a>
            </div>
        </div>
    </div>
</footer>
