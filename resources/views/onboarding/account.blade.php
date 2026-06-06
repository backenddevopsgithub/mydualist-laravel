<x-onboarding.layout
    step="account"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Create Your Account"
    subtitle="Let’s get you started on your journey of collecting and sharing dua requests."
>
    <form method="POST" action="{{ route('onboarding.store', 'account') }}" class="space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <x-onboarding.input name="first_name" label="First Name" placeholder="Enter your first name" autocomplete="given-name" required autofocus />
            <x-onboarding.input name="last_name" label="Last Name" placeholder="Enter your last name" autocomplete="family-name" required />
        </div>
        <x-onboarding.input name="email" label="Email Address" type="email" placeholder="Enter your email address" autocomplete="email" required />
        <x-onboarding.input name="password" label="Password" type="password" placeholder="Create a strong password" autocomplete="new-password" required />
        <x-onboarding.input name="password_confirmation" label="Confirm Password" type="password" placeholder="Confirm your password" autocomplete="new-password" required />

        <label class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-stone-200 bg-white p-4 text-[1.075rem] leading-7 text-stone-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50/40">
            <input
                type="checkbox"
                name="terms"
                value="1"
                @checked(old('terms'))
                class="mt-1 h-6 w-6 rounded-lg border-2 border-stone-300 text-emerald-700 transition focus:ring-4 focus:ring-emerald-100 group-hover:border-emerald-500"
                required
            >
            <span>
                I agree to the Terms & Conditions and Privacy Policy.
                @error('terms')
                    <span class="mt-1 block font-medium text-red-600">{{ $message }}</span>
                @enderror
            </span>
        </label>

        <button type="submit" class="w-full rounded-xl bg-emerald-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
            Continue
        </button>

        <p class="text-center text-sm text-stone-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-emerald-800 hover:text-emerald-700">Sign in</a>
        </p>
    </form>
</x-onboarding.layout>
