<x-onboarding.layout
    step="verify"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Verify Your Email"
    subtitle="We sent a 4-digit verification code to {{ auth()->user()?->email }}."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'verify') }}"
        x-data="{
            digits: ['', '', '', ''],
            focus(index) {
                this.$refs[`code${index}`]?.focus()
                this.$refs[`code${index}`]?.select()
            },
            input(index, event) {
                const value = event.target.value.replace(/\D/g, '').slice(-1)
                this.digits[index] = value
                event.target.value = value
                if (value && index < 3) {
                    this.focus(index + 1)
                }
            },
            backspace(index, event) {
                if (event.key !== 'Backspace') return
                if (this.digits[index] === '' && index > 0) {
                    this.focus(index - 1)
                }
            },
            paste(event) {
                const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4)
                if (! pasted) return
                event.preventDefault()
                pasted.split('').forEach((digit, index) => {
                    this.digits[index] = digit
                    this.$refs[`code${index}`].value = digit
                })
                this.focus(Math.min(pasted.length, 4) - 1)
            },
        }"
    >
        @csrf

        <div>
            <div class="mx-auto grid max-w-sm grid-cols-4 gap-4">
                @for ($i = 0; $i < 4; $i++)
                    <input
                        name="code[]"
                        x-ref="code{{ $i }}"
                        x-model="digits[{{ $i }}]"
                        x-on:input="input({{ $i }}, $event)"
                        x-on:keydown="backspace({{ $i }}, $event)"
                        x-on:paste="paste"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="one-time-code"
                        maxlength="1"
                        value="{{ old("code.{$i}") }}"
                        class="h-16 rounded-2xl border border-stone-200 bg-white text-center text-2xl font-bold text-stone-950 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100 sm:h-20 sm:text-3xl"
                        required
                    >
                @endfor
            </div>

            @error('code')
                <p class="mt-3 text-center text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror

            <p class="mt-6 text-center text-sm text-stone-600">
                Didn't receive the code? Check your spam folder or resend below.
            </p>
            @if (session('resend_status'))
                <p class="mt-2 text-center text-sm font-semibold text-emerald-800">{{ session('resend_status') }}</p>
            @endif
        </div>

        <x-onboarding.actions back="image" submit="Next" />
    </form>

    <form method="POST" action="{{ route('onboarding.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">Resend verification code</button>
    </form>
</x-onboarding.layout>
