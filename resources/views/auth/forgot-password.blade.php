<x-auth.layout
    title="Reset Password"
    subtitle="We will send you an email with instructions on how to reset your password."
    eyebrow="Secure & Private"
    side-title="Reset your<br><span class='text-emerald-700'>password</span>"
    side-description="No worries! Enter your email address and we'll send you instructions to reset your password and get back to your account."
    icon="mail"
    :back-href="route('login')"
    back-label="Back to login"
>
    <x-slot:side>
        <div class="mt-10 max-w-sm rounded-2xl bg-emerald-50/80 p-6 ring-1 ring-emerald-900/5">
            <div class="flex gap-5">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4.5 6.5h15v11h-15v-11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m5 7 7 6 7-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-stone-950">Check your inbox</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">
                        We'll send a password reset link to your email address. Please check your inbox and spam folder.
                    </p>
                </div>
            </div>
        </div>
    </x-slot:side>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <x-auth.input
            name="email"
            label="Email address"
            type="email"
            placeholder="Enter your email address"
            autocomplete="email"
            required
            autofocus
        />

        <button type="submit" class="w-full rounded-xl bg-emerald-800 px-5 py-4 text-base font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
            Send reset link
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
