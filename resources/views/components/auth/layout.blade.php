@props([
    'title',
    'subtitle',
    'eyebrow' => 'Trusted by thousands of Muslims',
    'sideTitle',
    'sideDescription',
    'icon' => 'lock',
    'backHref' => null,
    'backLabel' => null,
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
    <body class="min-h-screen bg-[#f8fbf9] font-sans text-stone-950 antialiased">
        <main class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_14%_16%,rgba(16,185,129,0.13),transparent_30%),radial-gradient(circle_at_82%_10%,rgba(6,95,70,0.08),transparent_32%)]"></div>
            <div class="absolute bottom-0 left-0 -z-10 h-44 w-full bg-gradient-to-t from-emerald-50/90 to-transparent"></div>

            <div class="mx-auto grid min-h-screen max-w-7xl px-5 py-8 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16 lg:px-10 lg:py-10">
                <section class="relative flex min-h-[34rem] flex-col justify-between pb-12 lg:min-h-0">
                    <div>
                        <x-home.logo />

                        <div class="mt-16 max-w-xl sm:mt-24 lg:mt-28">
                            <p class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-900/5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs">☑</span>
                                {{ $eyebrow }}
                            </p>

                            <h1 class="mt-7 text-5xl font-extrabold leading-tight tracking-tight sm:text-6xl">
                                {!! $sideTitle !!}
                            </h1>

                            <p class="mt-7 max-w-md text-lg leading-8 text-stone-600">
                                {{ $sideDescription }}
                            </p>

                            {{ $side ?? '' }}
                        </div>
                    </div>

                    <div class="pointer-events-none absolute inset-x-[-4rem] bottom-0 -z-10 h-48 text-emerald-950/10 sm:h-64">
                        <svg class="h-full w-full" viewBox="0 0 720 260" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                            <path d="M0 238h720v22H0z"/>
                            <path d="M72 238V126l15-18 15 18v112H72Zm8-123V86h14v29H80Zm185 123V143c0-54 95-54 95 0v95h-95Zm-29 0v-58c0-34 58-34 58 0v58h-58Zm147 0v-58c0-34 58-34 58 0v58h-58Zm165 0V118l12-14 12 14v120h-24Zm7-128V71h10v39h-10Z"/>
                            <path d="M120 238v-42c0-30 56-30 56 0v42h-56Zm380 0v-42c0-30 56-30 56 0v42h-56Z"/>
                        </svg>
                    </div>
                </section>

                <section class="flex items-center justify-center py-10 lg:py-0">
                    <div class="w-full max-w-xl">
                        @if ($backHref && $backLabel)
                            <div class="mb-8 flex justify-end">
                                <a href="{{ $backHref }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-800 hover:text-emerald-700">
                                    <span aria-hidden="true">←</span>
                                    {{ $backLabel }}
                                </a>
                            </div>
                        @endif

                        <div class="rounded-[2rem] border border-emerald-950/5 bg-white p-7 shadow-[0_30px_100px_rgba(15,23,42,0.08)] sm:p-10 lg:p-12">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-emerald-800">
                                @if ($icon === 'mail')
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4.5 6.5h15v11h-15v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="m5 7 7 6 7-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @else
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                @endif
                            </div>

                            <div class="mt-7 text-center">
                                <h2 class="text-3xl font-extrabold tracking-tight">{{ $title }}</h2>
                                <p class="mt-3 text-base leading-7 text-stone-600">{{ $subtitle }}</p>
                            </div>

                            <div class="mt-9">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="px-5 pb-6 text-center text-sm text-stone-600">
                <p class="inline-flex items-center gap-3">
                    <span class="text-emerald-800">♡</span>
                    Your privacy is our priority. We never share your data.
                </p>
            </div>
        </main>
    </body>
</html>
