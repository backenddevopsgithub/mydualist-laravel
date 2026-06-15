<footer class="bg-emerald-950 text-white">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="flex flex-col gap-10 md:flex-row md:items-start md:justify-between md:gap-12 lg:gap-16">
            <div class="max-w-xs space-y-4 md:shrink-0 lg:max-w-sm">
                <x-home.logo class="[&_img]:h-10 [&_img]:brightness-0 [&_img]:invert" />
                <p class="text-sm leading-7 text-emerald-100/80">
                    Your companion for collecting, organizing and sharing duas with ease — built for Hajj, Umrah, and every moment that matters.
                </p>
            </div>

            <div class="flex flex-col gap-10 sm:flex-row sm:items-start sm:gap-12 md:gap-16 lg:gap-20">
                <div class="flex flex-col gap-4">
                    <a href="{{ route('home') }}" class="text-sm text-emerald-50/90 transition hover:text-white">Home</a>
                    <a href="{{ route('home') }}#features" class="text-sm text-emerald-50/90 transition hover:text-white">Features</a>
                    <a href="{{ route('home') }}#how-it-works" class="text-sm text-emerald-50/90 transition hover:text-white">Submit a Dua</a>
                </div>

                <div class="flex flex-col gap-4">
                    <a href="{{ route('home') }}#pricing" class="text-sm text-emerald-50/90 transition hover:text-white">Pricing</a>
                    <a href="{{ route('onboarding.start') }}" class="text-sm text-emerald-50/90 transition hover:text-white">Create Dua List</a>
                    <a href="{{ route('blogs.index') }}" class="text-sm text-emerald-50/90 transition hover:text-white">Dua Resources</a>
                </div>

                <div class="flex flex-col gap-4 text-sm text-emerald-50/90 [&_a]:transition [&_a]:hover:text-white">
                    @include('partials.cms-footer-links')
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-white/20 pt-8 md:flex-row md:items-center md:justify-between">
            <a
                href="https://www.thepilgrim.co"
                target="_blank"
                rel="noopener noreferrer"
                class="text-sm font-semibold text-emerald-100/80 transition hover:text-white"
            >
                Powered by Pilgrim
            </a>
            <p class="text-xs font-semibold text-emerald-200/70">© {{ date('Y') }} My Dua List. All rights reserved.</p>
        </div>
    </div>
</footer>
