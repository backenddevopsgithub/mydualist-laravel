<section class="mt-16 overflow-hidden rounded-3xl bg-[#0c4b4f] px-6 py-12 text-white sm:px-10 sm:py-14">
    <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
        <div>
            <p class="text-3xl font-extrabold leading-tight sm:text-4xl">Get one Dua a week in your inbox.</p>
            <p class="mt-4 text-lg leading-8 text-emerald-50/90">Imagine being able to implement 52 Duas this year?</p>
        </div>

        <div>
            @if (session('newsletter_status'))
                <p class="rounded-2xl bg-white/10 px-5 py-4 text-base font-semibold text-white ring-1 ring-white/15">{{ session('newsletter_status') }}</p>
            @else
                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="source" value="article">
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Email address"
                        required
                        class="w-full rounded-2xl border border-white/10 bg-white/95 px-4 py-3.5 text-base text-stone-900 outline-none ring-emerald-300/40 focus:ring-2"
                    >
                    @error('email')
                        <p class="text-sm font-medium text-red-200">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="rounded-2xl bg-[#171717] px-6 py-3.5 text-base font-bold text-white transition hover:bg-black">
                        Submit
                    </button>
                    <p class="text-sm leading-6 text-emerald-50/80">By signing up, you agree to our terms and privacy policy.</p>
                </form>
            @endif
        </div>
    </div>
</section>
