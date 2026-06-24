<x-onboarding.layout
    step="account"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Create Your Account"
    subtitle="Let's get you started on your journey of collecting and sharing dua requests."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'account') }}"
        class="space-y-5"
        x-data="{
            firstName: @js(old('first_name', '')),
            lastName: @js(old('last_name', '')),
            email: @js(old('email', '')),
            password: '',
            passwordConfirmation: '',
            gender: @js(old('gender', '')),
            terms: @js((bool) old('terms')),
            passwordMeetsRequirements(value) {
                return /^(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}$/.test(value);
            },
            get canSubmit() {
                return this.firstName.trim() !== ''
                    && this.lastName.trim() !== ''
                    && this.email.trim() !== ''
                    && this.passwordMeetsRequirements(this.password)
                    && this.password === this.passwordConfirmation
                    && this.gender !== ''
                    && this.terms;
            },
        }"
    >
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <x-onboarding.input name="first_name" label="First Name" placeholder="Enter your first name" autocomplete="given-name" required autofocus x-model="firstName" />
            <x-onboarding.input name="last_name" label="Last Name" placeholder="Enter your last name" autocomplete="family-name" required x-model="lastName" />
        </div>

        <div>
            <p class="block text-[1.075rem] font-bold text-stone-900">
                Gender<span class="ui-label-required" aria-hidden="true"> *</span>
            </p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                @foreach (['male' => 'Male', 'female' => 'Female'] as $value => $label)
                    <label
                        class="cursor-pointer rounded-2xl border px-4 py-4 text-center text-[1.075rem] font-semibold transition"
                        x-bind:class="gender === '{{ $value }}'
                            ? 'border-emerald-800 bg-emerald-800 text-white'
                            : 'border-stone-200 bg-white text-stone-700 hover:border-emerald-300'"
                    >
                        <input type="radio" name="gender" value="{{ $value }}" class="sr-only" x-model="gender" required>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('gender') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-onboarding.input name="email" label="Email Address" type="email" placeholder="Enter your email address" autocomplete="email" required x-model="email" />
        <x-onboarding.input
            name="password"
            label="Password"
            type="password"
            placeholder="Create a strong password"
            autocomplete="new-password"
            description="Password must contain at least 1 capital letter, 1 special character, and be at least 8 characters long."
            required
            x-model="password"
        />
        <x-onboarding.input name="password_confirmation" label="Confirm Password" type="password" placeholder="Confirm your password" autocomplete="new-password" required x-model="passwordConfirmation" />

        <label class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-stone-200 bg-white p-4 text-[1.075rem] leading-7 text-stone-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50/40">
            <input
                type="checkbox"
                name="terms"
                value="1"
                x-model="terms"
                class="mt-1 h-6 w-6 rounded-lg border-2 border-stone-300 text-emerald-700 transition focus:ring-4 focus:ring-emerald-100 group-hover:border-emerald-500"
                required
            >
            <span>
                I agree to the terms and conditions, and I consent to the processing of my personal data in accordance with the Privacy Policy, as required by GDPR.
                @error('terms')
                    <span class="mt-1 block font-medium text-red-600">{{ $message }}</span>
                @enderror
            </span>
        </label>

        <x-ui.button
            type="submit"
            variant="primary"
            size="lg"
            full-width
            disabled
            x-bind:disabled="! canSubmit"
        >
            Get started
        </x-ui.button>

        <p class="text-center text-sm text-stone-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-emerald-800 hover:text-emerald-700">Sign in</a>
        </p>
    </form>
</x-onboarding.layout>
