<x-auth.layout
    title="Sign In"
    subtitle="Enter your details to access your account"
    side-title="Welcome Back<br><span class='text-emerald-700'>to My Dua List</span>"
    side-description="Continue your journey of faith. Manage your dua lists, collect requests and pray for others."
>
    <x-slot:side>
        <div class="mt-10 grid max-w-md gap-6">
            @foreach ([
                ['100% Private & Secure', 'Your duas and information are always kept private.'],
                ['Easy to Use', 'Create lists, share and collect dua requests in seconds.'],
                ['Community Driven', 'Join a global community and pray for one another.'],
            ] as [$title, $text])
                <div class="flex gap-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-800 ring-1 ring-emerald-900/5">✓</div>
                    <div>
                        <h2 class="font-bold text-stone-950">{{ $title }}</h2>
                        <p class="mt-1 text-sm leading-6 text-stone-600">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot:side>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

        <x-auth.input
            name="email"
            label="Email address"
            type="email"
            placeholder="Enter your email"
            autocomplete="email"
            required
            autofocus
        />

        <x-auth.input
            name="password"
            label="Password"
            type="password"
            placeholder="Enter your password"
            icon="lock"
            autocomplete="current-password"
            required
        />

        <div class="flex items-center justify-between gap-4">
            <label class="inline-flex items-center gap-3 text-sm font-medium text-stone-700">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                    class="h-4 w-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-700"
                >
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-800 px-5 py-4 text-base font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
            Sign In
        </button>
    </form>

    <div class="my-7 flex items-center gap-4 text-sm text-stone-500">
        <span class="h-px flex-1 bg-stone-200"></span>
        or
        <span class="h-px flex-1 bg-stone-200"></span>
    </div>

    <p class="text-center text-base text-stone-600">
        Don't have an account?
        <a href="{{ route('onboarding.start') }}" class="font-bold text-emerald-800 hover:text-emerald-700">Create Account</a>
    </p>
</x-auth.layout>
