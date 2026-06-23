@php
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
    $duaErrors = collect($errors->getMessages())->filter(fn ($_, $key) => str_starts_with($key, 'duas.'));
    $firstErrorIndex = $duaErrors->keys()->map(fn ($k) => (int) str_replace('duas.', '', $k))->sort()->first();
    $duaErrorIndexes = $duaErrors->keys()->map(fn ($k) => (int) str_replace('duas.', '', $k))->values()->all();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Submit a dua request for {{ $duaList->title }} on My Dua List.">
        <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <title>{{ $duaList->title }} - My Dua List</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf7] font-sans text-stone-950 antialiased">
        <main class="min-h-screen">
            <div class="relative mx-auto max-w-3xl px-5 py-6 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between">
                    <x-home.logo />
                    <a href="{{ route('home') }}" class="rounded-2xl bg-white/80 px-4 py-2 text-sm font-bold text-emerald-950 shadow-sm ring-1 ring-emerald-950/10">Home</a>
                </header>

                <article class="mx-auto mt-8 overflow-hidden rounded-[2.25rem] border border-emerald-950/10 bg-white shadow-[0_30px_100px_rgba(15,23,42,0.10)]">
                    <div class="p-6 text-center sm:p-10">
                        <div class="flex flex-wrap justify-center gap-2">
                            <span class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-800">{{ $duaList->occasionLabel() }}</span>
                            @if ($duaList->daysRemainingLabel())
                                <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-extrabold text-amber-800">{{ $duaList->daysRemainingLabel() }}</span>
                            @endif
                        </div>

                        <h1 class="dashboard-page-title mt-6 text-stone-950">{{ $duaList->title }}</h1>

                        @if ($acceptsSubmissions)
                            <p class="mx-auto mt-4 max-w-xl text-base leading-8 text-stone-600">
                                {{ $duaList->publicInviteMessage() }}
                            </p>
                        @else
                            <p class="mx-auto mt-4 max-w-xl text-base leading-8 text-stone-600">
                                {{ $closedReason ?? $duaList->publicClosedMessage() }}
                            </p>
                        @endif

                        <div class="mx-auto mt-8 max-w-md">
                            <div class="flex items-center justify-between text-sm font-bold text-stone-600">
                                <span>{{ $completedCount }} completed</span>
                                <span>{{ $progress }}%</span>
                                <span>{{ $totalSubmissions }} total</span>
                            </div>
                            <div class="mt-3 h-3 rounded-full bg-stone-100">
                                <div class="h-3 rounded-full bg-emerald-700" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    </div>
                </article>

                <section id="submit-dua" class="mx-auto mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_22px_70px_rgba(15,23,42,0.06)] sm:p-8">
                    @if (session('submission_status'))
                        <div class="mb-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                            {{ session('submission_status') }}
                        </div>

                        @if ($duaList->showsCreatorFeatures())
                            <div id="creator" class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-6 text-center">
                                <h3 class="text-lg font-extrabold text-stone-950">Donate to {{ trim($duaList->user->first_name ?? 'the list owner') }}’s Cause</h3>
                                @if (filled($duaList->donation_note))
                                    <p class="mt-3 text-sm leading-7 text-stone-700">{{ $duaList->donation_note }}</p>
                                @endif
                                @if (filled($duaList->donation_link))
                                    <a
                                        href="{{ $duaList->trackableDonationUrl() }}"
                                        class="mt-5 inline-flex items-center justify-center rounded-full bg-emerald-900 px-8 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800"
                                    >
                                        Support Now
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endif

                    @if ($acceptsSubmissions)
                        <div
                            x-data="publicSubmissionForm(@js([
                                'step' => $errors->any() && $duaErrors->isNotEmpty() ? 'duas' : 'info',
                                'duas' => old('duas', ['']),
                                'gender' => old('gender', ''),
                                'whatsapp' => (bool) old('whatsapp_notifications'),
                                'whatsappCountryCode' => old('whatsapp_country_code', '+44'),
                                'whatsappPhone' => old('whatsapp_phone', ''),
                                'whatsappVerified' => (bool) old('whatsapp_verification_token'),
                                'whatsappVerificationToken' => old('whatsapp_verification_token', ''),
                                'terms' => (bool) old('terms'),
                                'firstName' => old('first_name', ''),
                                'lastName' => old('last_name', ''),
                                'email' => old('email', ''),
                                'slug' => $duaList->slug,
                                'selectedSuggestionIds' => array_map('intval', old('suggestion_ids', [])),
                            ]))"
                            x-init="@if($firstErrorIndex !== null) $nextTick(() => document.getElementById('dua-field-{{ $firstErrorIndex }}')?.scrollIntoView({ behavior: 'smooth', block: 'center' })) @endif"
                        >
                            <form method="POST" action="{{ route('dua-lists.submissions.store', $duaList) }}" class="space-y-5" x-on:submit="submitForm($event)">
                                @csrf
                                <input type="hidden" name="submission_batch_key" x-bind:value="submissionBatchKey">
                                <div class="hidden" aria-hidden="true">
                                    <input name="website" tabindex="-1" autocomplete="off">
                                </div>

                                <div x-show="step === 'info'" x-cloak>
                                    <h2 class="text-2xl font-extrabold tracking-tight text-stone-950">Your details</h2>
                                    <p class="mt-2 text-sm text-stone-600">Tell us who is submitting these dua requests.</p>

                                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="first_name" class="block text-sm font-bold text-stone-900">First Name</label>
                                            <input id="first_name" name="first_name" x-model="firstName" required class="mt-2 block w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100 @error('first_name') border-red-400 @enderror">
                                            @error('first_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-sm font-bold text-stone-900">Last Name</label>
                                            <input id="last_name" name="last_name" x-model="lastName" required class="mt-2 block w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100 @error('last_name') border-red-400 @enderror">
                                            @error('last_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label for="email" class="block text-sm font-bold text-stone-900">Email</label>
                                        <input id="email" name="email" type="email" x-model="email" required class="mt-2 block w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100 @error('email') border-red-400 @enderror">
                                        @error('email') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="mt-5">
                                        <p class="block text-sm font-bold text-stone-900">Gender</p>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            @foreach (['male' => 'Male', 'female' => 'Female'] as $value => $label)
                                                <label class="cursor-pointer rounded-2xl border px-4 py-3 text-center text-sm font-bold transition" x-bind:class="gender === '{{ $value }}' ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-stone-200 bg-white text-stone-700'">
                                                    <input type="radio" name="gender" value="{{ $value }}" class="sr-only" x-model="gender" required>
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('gender') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl bg-emerald-50/70 p-4 text-sm font-semibold text-emerald-950 ring-1 ring-emerald-900/10">
                                        <input type="checkbox" name="whatsapp_notifications" value="1" x-model="whatsapp" class="mt-1 h-5 w-5 rounded border-emerald-200 text-emerald-800">
                                        <span>Would you like a WhatsApp notification when {{ trim($duaList->user->first_name ?? 'the list owner') }} completes your dua?</span>
                                    </label>

                                    <div
                                        x-show="whatsapp"
                                        x-cloak
                                        class="mt-4 space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4"
                                        x-effect="whatsapp && whatsappOtpStep === 'phone' && ! whatsappVerified ? ensureWhatsAppPhoneInputReady() : null"
                                    >
                                        <div x-show="! whatsappVerified">
                                            <div x-show="whatsappOtpStep === 'phone'" class="space-y-3">
                                                <div class="whatsapp-phone-field">
                                                    <label for="whatsapp_phone_input" class="block text-xs font-bold text-stone-700">WhatsApp number</label>
                                                    <input
                                                        id="whatsapp_phone_input"
                                                        type="tel"
                                                        x-ref="whatsappPhoneInput"
                                                        x-on:input="onWhatsAppPhoneInput()"
                                                        x-on:countrychange="onWhatsAppPhoneInput()"
                                                        inputmode="tel"
                                                        autocomplete="tel"
                                                        class="mt-1 block w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100"
                                                        aria-describedby="whatsapp_phone_help whatsapp_phone_country"
                                                    >
                                                    <p id="whatsapp_phone_help" class="mt-2 text-xs text-stone-500">Select your country, then enter your local number. We'll send a verification code on WhatsApp.</p>
                                                    <p id="whatsapp_phone_country" class="sr-only" x-text="whatsappPhoneCountryLabel ? `Selected country: ${whatsappPhoneCountryLabel}` : 'No country selected yet.'"></p>
                                                    <input type="hidden" name="whatsapp_country_code" x-bind:value="whatsappCountryCode">
                                                    <input type="hidden" name="whatsapp_phone" x-bind:value="whatsappPhone">
                                                </div>

                                                <div class="mt-3">
                                                    <button
                                                        type="button"
                                                        x-on:click="sendWhatsAppOtp()"
                                                        x-bind:disabled="whatsappOtpSending || ! whatsappPhoneValid"
                                                        class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-400"
                                                    >
                                                        <span x-text="whatsappOtpSending ? 'Sending...' : 'Verify via WhatsApp'"></span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div x-show="whatsappOtpStep === 'otp'" class="space-y-3">
                                                <div>
                                                    <label for="whatsapp_otp" class="block text-xs font-bold text-stone-700">Check WhatsApp for OTP</label>
                                                    <input id="whatsapp_otp" x-model="whatsappOtp" maxlength="6" inputmode="numeric" class="mt-1 block w-full rounded-xl border border-stone-200 px-3 py-2 text-sm tracking-[0.3em]" placeholder="Enter OTP">
                                                </div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <button type="button" x-on:click="verifyWhatsAppOtp()" x-bind:disabled="whatsappOtpVerifying || whatsappOtp.trim() === ''" class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-bold text-white disabled:bg-stone-400">
                                                        <span x-text="whatsappOtpVerifying ? 'Verifying...' : 'Verify OTP'"></span>
                                                    </button>
                                                    <button type="button" x-show="! whatsappOtpResent" x-on:click="resendWhatsAppOtp()" x-bind:disabled="whatsappOtpSending" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">
                                                        Resend OTP
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <p x-show="whatsappOtpError" x-text="whatsappOtpError" class="text-sm font-medium text-red-600"></p>
                                        <p x-show="whatsappOtpMessage" x-text="whatsappOtpMessage" class="text-sm font-semibold text-emerald-700"></p>
                                        <p x-show="whatsappVerified" class="text-sm font-semibold text-emerald-700">WhatsApp verification completed!</p>

                                        <input type="hidden" name="whatsapp_verification_token" x-bind:value="whatsappVerificationToken">
                                        @error('whatsapp_verification_token') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                        @error('whatsapp_phone') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 p-4 text-sm leading-6 text-stone-700">
                                        <input type="checkbox" name="terms" value="1" x-model="terms" class="mt-1 h-5 w-5 rounded border-stone-300 text-emerald-800" required>
                                        <span>I agree to the terms and conditions, and I consent to the processing of my personal data in accordance with the Privacy Policy, as required by GDPR.</span>
                                    </label>
                                    @error('terms') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                                    <button type="button" x-on:click="openDuas()" x-bind:disabled="! canContinue" class="mt-7 w-full rounded-2xl px-6 py-4 text-sm font-extrabold text-white transition disabled:cursor-not-allowed disabled:bg-stone-300" x-bind:class="canContinue ? 'bg-emerald-900 hover:bg-emerald-800' : 'bg-stone-300'">
                                        Next
                                    </button>
                                </div>

                                <div x-show="step === 'duas'" x-cloak>
                                    <div class="flex items-center justify-between gap-3">
                                        <h2 class="text-2xl font-extrabold tracking-tight text-stone-950">Your dua requests</h2>
                                        <button type="button" x-on:click="step = 'info'" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">Go back</button>
                                    </div>
                                    <p class="mt-2 text-sm text-stone-600">Write each dua in third person form. Add up to 35 duas.</p>

                                    <div class="mt-5 space-y-3">
                                        <template x-for="(dua, index) in duas" :key="'dua-' + index + '-' + duas.length">
                                            <div
                                                class="rounded-2xl border p-3 transition"
                                                x-bind:class="[
                                                    @js($duaErrorIndexes).includes(index) ? 'border-red-400 bg-red-50' : (activeDuaIndex === index ? 'border-emerald-700 bg-emerald-50/40 ring-2 ring-emerald-100' : 'border-stone-200 bg-white'),
                                                ]"
                                                x-bind:id="'dua-field-' + index"
                                            >
                                                <div class="mb-2 flex items-center justify-between gap-3">
                                                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-stone-500" x-text="'Dua ' + (index + 1)"></p>
                                                    <button type="button" x-show="duas.length > 1" x-on:click="removeDua(index)" class="rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold text-red-700">Remove</button>
                                                </div>
                                                <textarea
                                                    name="duas[]"
                                                    rows="4"
                                                    x-model="duas[index]"
                                                    x-on:focus="setActiveDuaIndex(index)"
                                                    x-on:click="setActiveDuaIndex(index)"
                                                    maxlength="1500"
                                                    placeholder="Write your dua here..."
                                                    class="block w-full rounded-xl border border-stone-100 bg-stone-50 px-4 py-3 text-base leading-7 outline-none focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-100 sm:text-sm"
                                                    required
                                                ></textarea>
                                                @foreach ($duaErrors as $field => $messages)
                                                    @if (preg_match('/^duas\.(\d+)$/', $field, $m))
                                                        <p x-show="index === {{ $m[1] }}" x-cloak class="mt-2 text-sm font-medium text-red-600">{{ $messages[0] }}</p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </template>
                                    </div>

                                    <div
                                        class="mt-6 rounded-2xl border border-emerald-900/10 bg-emerald-50/40 p-4 sm:p-5"
                                        aria-live="polite"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-lg font-extrabold text-stone-950">Suggestions</h3>
                                                <p class="mt-1 text-sm text-stone-600">Tap a suggestion to add it to the dua field you are currently editing.</p>
                                            </div>
                                            <span x-show="suggestionsLoading" x-cloak class="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-900/10">Loading</span>
                                        </div>

                                        <p x-show="suggestionsLoadError" x-cloak class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-200">
                                            <span x-text="suggestionsLoadError"></span>
                                        </p>

                                        <template x-for="section in suggestionSections" :key="section.key">
                                            <div
                                                x-show="hasSuggestionSection(section.key)"
                                                class="mt-5"
                                                x-bind:id="'suggestions-' + section.key"
                                            >
                                                <h4 class="text-sm font-extrabold uppercase tracking-[0.12em] text-emerald-900" x-text="section.label"></h4>

                                                <ul class="mt-3 space-y-2" role="list">
                                                    <template x-for="suggestion in visibleSuggestions(section.key)" :key="section.key + '-' + suggestion.id">
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="w-full rounded-2xl border border-emerald-900/10 bg-white px-4 py-3 text-left text-sm leading-6 text-stone-800 shadow-sm transition hover:border-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                                                x-on:click="selectSuggestion(suggestion)"
                                                                x-bind:aria-label="'Add suggestion: ' + suggestion.title"
                                                            >
                                                                <span class="block font-bold text-stone-950" x-text="suggestion.title"></span>
                                                                <span
                                                                    x-show="suggestion.source_reference"
                                                                    class="mt-1 block text-xs font-semibold text-emerald-800"
                                                                    x-text="'Source: ' + suggestion.source_reference"
                                                                ></span>
                                                            </button>
                                                        </li>
                                                    </template>
                                                </ul>

                                                <button
                                                    type="button"
                                                    x-show="hasMoreSuggestions(section.key)"
                                                    class="mt-3 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-extrabold text-emerald-900 ring-1 ring-emerald-900/10 transition hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                                    x-on:click="showMoreSuggestions(section.key)"
                                                    x-bind:aria-expanded="expandedSuggestionSections[section.key] ? 'true' : 'false'"
                                                    x-bind:aria-controls="'suggestions-' + section.key"
                                                >
                                                    Show More
                                                </button>
                                            </div>
                                        </template>

                                        <template x-for="id in selectedSuggestionIds" :key="'suggestion-id-' + id">
                                            <input type="hidden" name="suggestion_ids[]" x-bind:value="id">
                                        </template>
                                    </div>

                                    <button type="button" x-on:click="addDua()" x-bind:disabled="duas.length >= maxDuas" class="mt-3 flex w-full items-center justify-center rounded-2xl border border-emerald-900/15 bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-950 transition hover:bg-emerald-100 disabled:opacity-50">
                                        + Add Another Dua
                                    </button>

                                    @error('duas') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                                    <button
                                        type="submit"
                                        x-bind:disabled="submitting"
                                        x-bind:aria-busy="submitting ? 'true' : 'false'"
                                        class="mt-7 w-full rounded-2xl bg-emerald-900 px-6 py-4 text-sm font-extrabold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-stone-400"
                                    >
                                        Submit Dua Requests<span x-show="submitting" x-cloak> — Submitting...</span>
                                    </button>
                                </div>
                            </form>

                            <div x-cloak x-show="showGuide" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4 backdrop-blur-sm">
                                <div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl sm:p-8">
                                    <h3 class="text-xl font-extrabold text-stone-950">Write duas in third person</h3>
                                    <p class="mt-4 text-sm leading-7 text-stone-600">
                                        Please write each dua in third person so the list owner can read it naturally when making dua for you.
                                        For example: "May Allah grant her good health" instead of "Grant me good health".
                                    </p>
                                    <button type="button" x-on:click="acceptGuide()" class="mt-6 w-full rounded-2xl bg-emerald-900 px-5 py-3.5 text-sm font-extrabold text-white transition hover:bg-emerald-800">
                                        I understand
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl bg-stone-50 p-5 text-sm leading-7 text-stone-700 ring-1 ring-stone-200">
                            <h2 class="text-xl font-extrabold text-stone-950">Submissions Closed</h2>
                            <p class="mt-2">
                                {{ $closedReason ?? $duaList->publicClosedMessage() }}
                            </p>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('home') }}#blog" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Submit Community Dua Instead</a>
                            <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-900/15 bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-950">Create your own Dua list</a>
                        </div>
                    @endif
                </section>
            </div>
        </main>

        @include('partials.public-legal-footer')

        @if ($duaList->showsCreatorFeatures() && session('submission_status'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const target = document.getElementById('creator');
                    if (! target || target.classList.contains('tracked')) {
                        return;
                    }

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (! entry.isIntersecting) {
                                return;
                            }

                            observer.unobserve(target);
                            target.classList.add('tracked');

                            fetch(@js(route('fundraising.track-view', $duaList)), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                    'Accept': 'application/json',
                                },
                            });
                        });
                    }, { threshold: 0.5 });

                    observer.observe(target);
                });
            </script>
        @endif
    </body>
</html>
