@php
    $coverImageUrl = $duaList->coverImageUrl();
    $creatorName = trim(($duaList->user->first_name ?? '').' '.($duaList->user->last_name ?? '')) ?: $duaList->user->name;
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Submit a dua request for {{ $duaList->title }} on My Dua List.">
        <title>{{ $duaList->title }} - My Dua List</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf7] font-sans text-stone-950 antialiased">
        <main class="min-h-screen overflow-hidden">
            <section class="relative">
                <div class="absolute inset-x-0 top-0 h-80 bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.16),transparent_30%),linear-gradient(135deg,#f7f2e7,#eef7ef)]"></div>

                <div class="relative mx-auto max-w-5xl px-5 py-6 sm:px-6 lg:px-8">
                    <header class="flex items-center justify-between">
                        <x-home.logo />
                        <a href="{{ route('home') }}" class="rounded-2xl bg-white/80 px-4 py-2 text-sm font-bold text-emerald-950 shadow-sm ring-1 ring-emerald-950/10">Home</a>
                    </header>

                    <article class="mx-auto mt-10 max-w-3xl overflow-hidden rounded-[2.25rem] border border-emerald-950/10 bg-white shadow-[0_30px_100px_rgba(15,23,42,0.10)]">
                        <div class="h-64 bg-emerald-50 sm:h-80">
                            @if ($coverImageUrl)
                                <img src="{{ $coverImageUrl }}" alt="{{ $duaList->title }} cover image" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_35%_20%,rgba(245,158,11,0.28),transparent_28%),linear-gradient(135deg,#064e3b,#f7f0dc)] text-white">
                                    <div class="text-center">
                                        <svg class="mx-auto h-16 w-16 opacity-90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-4 text-sm font-bold uppercase tracking-[0.18em] text-white/80">My Dua List</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="p-6 text-center sm:p-10">
                            <div class="flex flex-wrap justify-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-800">{{ $duaList->occasionLabel() }}</span>
                                @if ($duaList->daysRemainingLabel())
                                    <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-extrabold text-amber-800">{{ $duaList->daysRemainingLabel() }}</span>
                                @endif
                            </div>

                            <h1 class="mt-6 font-serif text-4xl font-bold tracking-tight text-stone-950 sm:text-6xl">{{ $duaList->title }}</h1>
                            <p class="mx-auto mt-4 max-w-xl text-base leading-8 text-stone-600">
                                {{ $creatorName }} is collecting dua requests for {{ $duaList->occasionLabel() }}. Add your request and be part of something meaningful.
                            </p>

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

                            <div class="mt-9 grid gap-3 sm:grid-cols-2">
                                <a href="#submit-dua" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-6 py-4 text-sm font-extrabold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                    Submit a Dua Request
                                </a>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/10 bg-emerald-50 px-6 py-4 text-sm font-extrabold text-emerald-950 transition hover:bg-emerald-100"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard?.writeText(@js($duaList->publicUrl())); copied = true; setTimeout(() => copied = false, 1800)"
                                >
                                    <span x-show="! copied">Copy Share Link</span>
                                    <span x-cloak x-show="copied">Copied</span>
                                </button>
                            </div>
                        </div>
                    </article>

                    <section id="submit-dua" class="mx-auto mt-8 max-w-3xl rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_22px_70px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-2xl font-extrabold tracking-tight text-stone-950">Submit a dua request</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-600">
                            Request submission will be connected in the next product phase. This page is ready for the public sharing flow.
                        </p>
                    </section>
                </div>
            </section>
        </main>
    </body>
</html>
