<x-auth.layout
    title="Create New Password"
    subtitle="Choose a strong new password for your My Dua List account."
    eyebrow="Secure & Private"
    side-title="Create a new<br><span class='text-emerald-700'>password</span>"
    side-description="Your reset link has been verified. Enter a new password to regain access to your dua lists."
    icon="lock"
    :back-href="route('login')"
    back-label="Back to login"
>
    <x-slot:side>
        <div class="mt-10 grid max-w-md gap-6">
            @foreach ([
                ['Use a strong password', 'Choose a password that is unique to your account.'],
                ['Your data stays private', 'We protect your account and dua lists with secure Laravel sessions.'],
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

    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf

        <input type="hidden" name="token" value="{{ old('token', $token) }}">

        <x-auth.input
            name="email"
            label="Email address"
            type="email"
            placeholder="Enter your email address"
            :value="$email"
            autocomplete="email"
            required
            autofocus
        />

        <x-auth.input
            name="password"
            label="New password"
            type="password"
            placeholder="Enter your new password"
            icon="lock"
            autocomplete="new-password"
            required
        />

        <x-auth.input
            name="password_confirmation"
            label="Confirm password"
            type="password"
            placeholder="Confirm your new password"
            icon="lock"
            autocomplete="new-password"
            required
        />

        @error('token')
            <p class="text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit" class="w-full rounded-xl bg-emerald-800 px-5 py-4 text-base font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
            Reset password
        </button>
    </form>

    <div class="my-7 flex items-center gap-4 text-sm text-stone-500">
        <span class="h-px flex-1 bg-stone-200"></span>
        or
        <span class="h-px flex-1 bg-stone-200"></span>
    </div>

    <p class="text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 font-bold text-emerald-800 hover:text-emerald-700">
            <span aria-hidden="true">←</span>
            Return to login
        </a>
    </p>
</x-auth.layout>
