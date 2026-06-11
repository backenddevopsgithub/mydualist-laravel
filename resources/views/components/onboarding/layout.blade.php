@props([
    'step',
    'stepIndex',
    'totalSteps',
    'title',
    'subtitle',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $subtitle }}">

        <title>{{ $title }} - My Dua List</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#eef6f2] font-sans text-stone-950 antialiased">
        <main class="relative min-h-screen">
            <div class="absolute inset-x-0 bottom-0 -z-10 h-64 bg-gradient-to-t from-emerald-100/70 to-transparent"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 -z-10 h-56 w-96 text-emerald-950/10">
                <svg class="h-full w-full" viewBox="0 0 420 230" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                    <path d="M0 212h420v18H0z"/>
                    <path d="M48 212V105l13-15 13 15v107H48Zm7-112V65h12v35H55Zm136 112v-68c0-45 78-45 78 0v68h-78Zm-32 0v-43c0-28 48-28 48 0v43h-48Z"/>
                </svg>
            </div>

            <div class="mx-auto flex min-h-screen max-w-4xl flex-col px-5 py-6 sm:px-8">
                <header class="flex items-center justify-between">
                    <x-home.logo />
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-900">
                        Step {{ $stepIndex }} of {{ $totalSteps }}
                    </p>
                </header>

                <section class="flex flex-1 items-center justify-center py-8">
                    <x-ui.card class="w-full shadow-[0_30px_100px_rgba(15,23,42,0.08)] sm:p-9 lg:p-11">
                        <x-onboarding.stepper :current="$stepIndex" :total="$totalSteps" />

                        <div class="mx-auto mt-8 max-w-2xl text-center">
                            <h1 class="text-3xl font-extrabold tracking-tight text-emerald-950 sm:text-4xl">{{ $title }}</h1>
                            <p class="mt-3 text-sm leading-6 text-stone-600 sm:text-base">{{ $subtitle }}</p>
                        </div>

                        <div class="mx-auto mt-9 max-w-2xl">
                            {{ $slot }}
                        </div>
                    </x-ui.card>
                </section>
            </div>
        </main>
    </body>
</html>
