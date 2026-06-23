@props([
    'title' => 'Dashboard - My Dua List',
    'user',
])

@php
    $initials = collect(explode(' ', trim($user->name ?: $user->email)))
        ->filter()
        ->map(fn ($part) => Illuminate\Support\Str::substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|instrument-serif:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf7] font-sans text-stone-950 antialiased">
        <x-impersonation.banner />

        <div class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
            <x-dashboard.sidebar />

            <div class="min-w-0 pb-28 lg:pb-0">
                <header
                    class="sticky top-0 z-40 border-b border-stone-200 bg-[#fbfaf7]/95 backdrop-blur-xl"
                    x-data="{ open: false }"
                >
                    <div class="flex h-20 items-center justify-between px-5 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-4">
                            <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-950/10 bg-white text-emerald-950 shadow-sm lg:hidden" x-on:click="open = ! open" aria-label="Open navigation">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <div class="lg:hidden">
                                <x-home.logo />
                            </div>
                            <p class="hidden text-xs font-medium text-stone-600 lg:block">
                                Welcome back, <span class="text-emerald-900">{{ $user->first_name ?: Illuminate\Support\Str::before($user->name, ' ') }}</span>
                            </p>
                        </div>

                        <nav class="hidden items-center gap-3 text-xs font-bold text-stone-800 lg:flex" aria-label="Dashboard navigation">
                            <a href="{{ route('home') }}#resources" class="rounded-lg px-3 py-2 transition hover:bg-emerald-50 hover:text-emerald-900">Dua Resources</a>
                            <a href="{{ route('dashboard') }}" @class([
                                'rounded-lg px-3 py-2 transition hover:bg-emerald-50 hover:text-emerald-900',
                                'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard', 'dashboard.archived'),
                            ])>Dashboard</a>
                            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 transition hover:bg-emerald-50 hover:text-emerald-900">Home</a>
                        </nav>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('dashboard.profile') }}" class="hidden text-xs font-bold text-stone-800 transition hover:text-emerald-900 sm:inline">{{ $user->first_name ?: $user->name }}</a>
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-xs font-extrabold text-emerald-900 ring-1 ring-emerald-900/5">
                                {{ $initials ?: 'U' }}
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-emerald-950/10 bg-white px-5 py-4 shadow-xl shadow-emerald-950/5 lg:hidden" x-cloak x-show="open">
                        <x-dashboard.mobile-menu />
                    </div>
                </header>

                {{ $slot }}
            </div>
        </div>

        <x-dashboard.bottom-nav />
    </body>
</html>
