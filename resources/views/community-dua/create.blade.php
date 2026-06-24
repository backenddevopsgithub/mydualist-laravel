<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-seo.meta :seo="$seo" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main class="mx-auto max-w-2xl px-4 py-10 sm:py-14">
            <section class="space-y-4">
                <h1 class="text-3xl font-extrabold tracking-tight text-emerald-950 sm:text-4xl">
                    You&rsquo;re submitting a dua to the general Muslim community
                </h1>

                <div class="space-y-3">
                    <h2 class="text-xl font-extrabold text-emerald-950">How it works:</h2>
                    <p class="text-base leading-7 text-[#0C7663] sm:text-lg">
                        Community Duas allow for anyone in the world to submit a Du&rsquo;a to a Pilgrim visiting the lands of Makkah and Madinah. We&rsquo;ll do our best to get your Du&rsquo;a completed by an active Pilgrim. You can also pay it forward to get your Du&rsquo;a seen multiple times.
                    </p>
                </div>
            </section>

            <h2 class="mt-10 text-2xl font-extrabold text-emerald-950">Submit a dua request</h2>

            @if (session('status'))
                <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-900 ring-1 ring-red-200">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('community-dua.store') }}"
                class="mt-8 space-y-5 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm sm:p-8"
                x-data="communityDuaForm(@js([
                    'whatsapp' => (bool) old('whatsapp_notifications'),
                    'whatsappCountryCode' => old('whatsapp_country_code', '+44'),
                    'whatsappPhone' => old('whatsapp_phone', ''),
                    'whatsappVerified' => (bool) old('whatsapp_verification_token'),
                    'whatsappVerificationToken' => old('whatsapp_verification_token', ''),
                ]))"
                x-on:submit="submitForm($event)"
            >
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="ui-label-required text-sm font-bold text-stone-800" for="first_name">First name</label>
                        <input type="text" name="first_name" id="first_name" maxlength="15" required value="{{ old('first_name') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                    </div>
                    <div>
                        <label class="ui-label-required text-sm font-bold text-stone-800" for="last_name">Last name</label>
                        <input type="text" name="last_name" id="last_name" maxlength="15" required value="{{ old('last_name') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                    </div>
                </div>

                <div>
                    <label class="ui-label-required text-sm font-bold text-stone-800" for="email">Email address</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                </div>

                <fieldset>
                    <legend class="ui-label-required text-sm font-bold text-stone-800">Gender</legend>
                    <div class="mt-3 flex gap-4">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold">
                            <input type="radio" name="gender" value="male" @checked(old('gender') === 'male') required>
                            Male
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold">
                            <input type="radio" name="gender" value="female" @checked(old('gender') === 'female')">
                            Female
                        </label>
                    </div>
                </fieldset>

                <div class="space-y-4 border-t border-stone-100 pt-5">
                    <label class="flex items-start gap-3 text-sm text-stone-700">
                        <input
                            type="checkbox"
                            name="whatsapp_notifications"
                            value="1"
                            x-model="whatsapp"
                            x-on:change="if (! whatsapp) resetWhatsAppVerification()"
                            class="mt-1 h-5 w-5 rounded border-emerald-200 text-emerald-800"
                        >
                        <span>Would you like a Whatsapp notification when a pilgrim completes your dua?</span>
                    </label>

                    <div x-show="whatsapp" x-cloak class="space-y-4 rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4">
                        <div x-show="! whatsappVerified">
                            <div x-show="whatsappOtpStep === 'phone'" class="space-y-3">
                                <div class="whatsapp-phone-field">
                                    <label for="whatsapp_phone_input" class="block text-sm font-bold text-stone-800">Whatsapp Number</label>
                                    <input
                                        id="whatsapp_phone_input"
                                        type="tel"
                                        x-ref="whatsappPhoneInput"
                                        class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100"
                                        placeholder="Phone Number"
                                        aria-describedby="whatsapp_phone_help whatsapp_phone_country"
                                    >
                                    <p id="whatsapp_phone_help" class="mt-2 text-xs text-stone-500">Select your country, then enter your mobile number without the country code.</p>
                                    <p id="whatsapp_phone_country" class="sr-only" x-text="whatsappPhoneCountryLabel ? `Selected country: ${whatsappPhoneCountryLabel}` : 'No country selected yet.'"></p>
                                    <input type="hidden" name="whatsapp_country_code" x-bind:value="whatsappCountryCode">
                                    <input type="hidden" name="whatsapp_phone" x-bind:value="whatsappPhone">
                                </div>

                                <button
                                    type="button"
                                    x-on:click="sendWhatsAppOtp()"
                                    x-bind:disabled="whatsappOtpSending || ! whatsappPhoneValid"
                                    class="rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white disabled:bg-stone-400"
                                >
                                    <span x-text="whatsappOtpSending ? 'Sending...' : 'Verify'"></span>
                                </button>
                            </div>

                            <div x-show="whatsappOtpStep === 'otp'" class="space-y-3">
                                <div>
                                    <label for="whatsapp_otp" class="block text-sm font-bold text-stone-800">Check Whatsapp for OTP:</label>
                                    <input id="whatsapp_otp" x-model="whatsappOtp" maxlength="6" inputmode="numeric" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm tracking-[0.3em] outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" placeholder="Enter OTP">
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" x-on:click="verifyWhatsAppOtp()" x-bind:disabled="whatsappOtpVerifying || whatsappOtp.trim() === ''" class="rounded-2xl bg-emerald-800 px-5 py-3 text-sm font-extrabold text-white disabled:bg-stone-400">
                                        <span x-text="whatsappOtpVerifying ? 'Verifying...' : 'Verify'"></span>
                                    </button>
                                    <button type="button" x-show="! whatsappOtpResent" x-on:click="resendWhatsAppOtp()" x-bind:disabled="whatsappOtpSending" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">
                                        Resend OTP
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p x-show="whatsappVerified" class="text-base font-semibold text-[#24cc63]">Whatsapp Verification Completed!</p>

                        <p x-show="whatsappOtpError" x-text="whatsappOtpError" class="text-sm font-medium text-red-600"></p>
                        <p x-show="whatsappOtpMessage && ! whatsappVerified" x-text="whatsappOtpMessage" class="text-sm font-semibold text-emerald-700"></p>

                        <input type="hidden" name="whatsapp_verification_token" x-bind:value="whatsappVerificationToken">
                        @error('whatsapp_verification_token') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        @error('whatsapp_phone') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="ui-label-required text-sm font-bold text-stone-800" for="content">Your dua (third person, max 100 words)</label>
                    <textarea name="content" id="content" rows="6" required class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">{{ old('content') }}</textarea>
                </div>

                <label class="flex items-start gap-3 text-sm text-stone-700">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-1">
                    <span>I agree to the terms and conditions, and I consent to the processing of my personal data in accordance with the Privacy Policy, as required by GDPR.</span>
                </label>

                <div class="space-y-3 border-t border-stone-100 pt-5">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Submit to free community</button>
                    <button type="submit" formaction="{{ route('community-dua.checkout') }}" formmethod="post" class="w-full rounded-2xl border border-emerald-900 bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-900">
                        Pay it forward ({{ strtoupper($currency) }} {{ $communityDuaPrice }}) — 20 completions
                    </button>
                </div>
            </form>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
