<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo.meta :seo="$seo" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main class="mx-auto max-w-2xl px-4 py-10 sm:py-14">
            <h1 class="text-3xl font-extrabold tracking-tight text-emerald-950">Submit a Community Dua</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600">
                Anyone can submit a dua for pilgrims visiting Makkah and Madinah. Submit for free or pay it forward for greater visibility.
            </p>

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

            <form method="POST" action="{{ route('community-dua.store') }}" class="mt-8 space-y-5 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-stone-800" for="first_name">First name</label>
                        <input type="text" name="first_name" id="first_name" maxlength="15" required value="{{ old('first_name') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                    </div>
                    <div>
                        <label class="text-sm font-bold text-stone-800" for="last_name">Last name</label>
                        <input type="text" name="last_name" id="last_name" maxlength="15" required value="{{ old('last_name') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-bold text-stone-800" for="email">Email</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                </div>

                <fieldset>
                    <legend class="text-sm font-bold text-stone-800">Gender</legend>
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

                <div>
                    <label class="text-sm font-bold text-stone-800" for="content">Your dua (third person, max 100 words)</label>
                    <textarea name="content" id="content" rows="6" required class="mt-2 w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">{{ old('content') }}</textarea>
                </div>

                <label class="flex items-start gap-3 text-sm text-stone-700">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-1">
                    <span>I agree to the terms and conditions and consent to processing of my personal data.</span>
                </label>

                <div class="space-y-3 border-t border-stone-100 pt-5">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Submit to free community</button>
                    <button type="submit" formaction="{{ route('community-dua.checkout') }}" formmethod="post" class="w-full rounded-2xl border border-emerald-900 bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-900">
                        Pay it forward ({{ strtoupper($currency) }} {{ $communityDuaPrice }}) — 20 completions
                    </button>
                </div>
            </form>
        </main>
    </body>
</html>
