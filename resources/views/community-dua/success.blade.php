<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Community Dua Submitted - {{ config('mydualist.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main class="mx-auto max-w-2xl px-4 py-10 sm:py-14">
            <div class="rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <h1 class="text-3xl font-extrabold text-emerald-950">Request Submitted!</h1>

                @if ($payment)
                    <p class="mt-4 text-sm text-stone-600">Payment reference: #{{ $payment->id }}</p>
                @endif

                <p class="mt-4 text-sm leading-6 text-stone-700">
                    We will present your dua to active pilgrims after they complete their personal lists. You will receive an email each time someone completes your dua.
                </p>

                @if ($payment)
                    <p class="mt-3 text-sm leading-6 text-stone-700">
                        Because you paid it forward, your dua will stay in rotation until it receives at least 20 completions.
                    </p>
                @endif

                <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Back to home</a>
            </div>
        </main>

        @include('partials.marketing-footer')
    </body>
</html>
