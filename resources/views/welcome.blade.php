@php
    $features = [
        ['Collect & Organize', 'Save duas for any occasion and keep them organized in your app.'],
        ['Occasion Based', 'Find the perfect dua for every moment and life situation.'],
        ['Share & Inspire', 'Share your list with family and friends and inspire others.'],
        ['Access Anywhere', 'Access your duas anytime, anywhere on any device.'],
        ['Private & Secure', 'Your duas and lists are private and always protected.'],
        ['Stay Consistent', 'Build a daily habit and grow closer to Allah in every dua.'],
    ];

    $steps = [
        ['Create Your Account', 'Sign up in seconds and start your journey.'],
        ['Create Your List', 'Name your list and choose the occasion.'],
        ['Add & Collect Duas', 'Search, add and organize duas that matter to you.'],
        ['Share & Stay Inspired', 'Share your list and keep the blessings flowing.'],
    ];

    $posts = [
        ['Dua Guides', '10 Powerful Duas from the Quran for Every Muslim', 'May 18, 2024', '5 min read', 'from-emerald-950 via-stone-800 to-amber-200'],
        ['Reminders', 'How to Make Dua in Sujood (With Examples)', 'May 12, 2024', '4 min read', 'from-stone-900 via-emerald-900 to-stone-200'],
        ['Quran & Hadith', 'Dua is the Weapon of the Believer', 'May 5, 2024', '6 min read', 'from-emerald-950 via-stone-900 to-amber-100'],
        ['Lifestyle', 'Building a Daily Habit of Making Dua', 'Apr 28, 2024', '4 min read', 'from-stone-200 via-amber-100 to-emerald-50'],
        ['Reminders', 'The Best Times to Make Dua and Why They Matter', 'Apr 20, 2024', '6 min read', 'from-emerald-900 via-stone-800 to-amber-100'],
        ['Dua Guides', 'Duas for Peace of Mind and Inner Strength', 'Apr 15, 2024', '5 min read', 'from-stone-950 via-emerald-950 to-amber-200'],
    ];

    $plans = [
        ['Free', 'For getting started', '$0', ['Create up to 3 lists', 'Basic dua collections', 'Access on all devices', 'Share with family & friends'], 'Get Started Free', false],
        ['Premium', 'For individuals', '$4.99', ['Unlimited lists', 'Advanced search & filters', 'Offline access', 'Priority support', 'Ad-free experience'], 'Start 7-Day Free Trial', true],
        ['Family', 'For the whole family', '$9.99', ['Everything in Premium', 'Up to 6 family members', 'Family sharing', 'Premium support'], 'Start Family Plan', false],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Collect, organize, pray and share duas for every occasion in one calm, private app.">

        <title>My Dua List - Collect. Organize. Pray. Share.</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf5] font-sans text-stone-950 antialiased">
        <div class="min-h-screen overflow-hidden">
            <header
                class="sticky top-0 z-50 border-b border-emerald-950/10 bg-[#fbfaf5]/90 backdrop-blur-xl"
                x-data="{ open: false }"
            >
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
                    <x-home.logo />

                    <nav class="hidden items-center gap-8 text-sm font-bold text-stone-700 lg:flex" aria-label="Main navigation">
                        <a href="#home" class="border-b-2 border-emerald-800 pb-1 text-emerald-900">Home</a>
                        <a href="#resources" class="transition hover:text-emerald-800">Dua Resources</a>
                        <a href="#blog" class="transition hover:text-emerald-800">Blog</a>
                        <a href="#pricing" class="transition hover:text-emerald-800">Pricing</a>
                    </nav>

                    <div class="hidden items-center gap-3 lg:flex">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-950 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-900">
                                Login
                            </a>
                            <a href="{{ route('onboarding.start') }}" class="rounded-xl bg-emerald-950 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                Create My List
                            </a>
                        @endauth
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-950/10 bg-white text-emerald-950 shadow-sm lg:hidden"
                        aria-label="Open navigation"
                        x-on:click="open = ! open"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <div class="border-t border-emerald-950/10 bg-white px-5 py-4 shadow-xl shadow-emerald-950/5 lg:hidden" x-cloak x-show="open">
                    <div class="mx-auto grid max-w-7xl gap-2 text-sm font-bold text-stone-700">
                        <a href="#blog" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Blog</a>
                        <a href="#pricing" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Pricing</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-950 px-4 py-3 text-center text-white">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 hover:bg-emerald-50">Login</a>
                            <a href="{{ route('onboarding.start') }}" class="rounded-xl bg-emerald-950 px-4 py-3 text-center text-white">Create My List</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main id="home">
                <section class="relative bg-[#f7f4ea]">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_14%,rgba(16,185,129,0.12),transparent_28%),radial-gradient(circle_at_78%_18%,rgba(120,72,20,0.12),transparent_32%)]"></div>

                    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 pb-28 pt-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:pb-36 lg:pt-20">
                        <div class="max-w-2xl">
                            <p class="inline-flex items-center gap-2 rounded-lg border border-amber-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                Your Journey. Your Supplications. Your List.
                            </p>

                            <h1 class="mt-8 max-w-xl font-serif text-5xl font-bold leading-[0.98] tracking-tight text-stone-950 sm:text-6xl lg:text-7xl">
                                Collect. Organize. <span class="text-emerald-900">Pray. Share.</span>
                            </h1>

                            <p class="mt-6 max-w-xl text-base leading-8 text-stone-700">
                                My Dua List helps you collect and organize your duas for every occasion in life. Keep them close, stay consistent, and share the blessings.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-950 px-6 py-3 text-sm font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                    Create My Dua List
                                    <span aria-hidden="true">→</span>
                                </a>
                                <a href="#resources" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-950/20 bg-white/80 px-6 py-3 text-sm font-bold text-emerald-950 shadow-sm transition hover:border-emerald-800">
                                    Explore Dua Resources
                                </a>
                            </div>

                            <div class="mt-9 flex flex-wrap items-center gap-4">
                                <div class="flex -space-x-3">
                                    <span class="h-10 w-10 rounded-full border-2 border-white bg-amber-200"></span>
                                    <span class="h-10 w-10 rounded-full border-2 border-white bg-emerald-200"></span>
                                    <span class="h-10 w-10 rounded-full border-2 border-white bg-stone-300"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-extrabold text-emerald-950">50,000+ Muslims</p>
                                    <p class="text-xs text-stone-600">organizing their duas with ease</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative min-h-[34rem] overflow-hidden rounded-[2.5rem] bg-[radial-gradient(circle_at_70%_30%,rgba(255,255,255,0.8),transparent_22%),linear-gradient(135deg,#e2d8c1,#f9f4e8_45%,#bda97b)] shadow-[0_35px_120px_rgba(45,35,20,0.2)]">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_72%_45%,rgba(6,78,59,0.18),transparent_24%)]"></div>
                            <div class="absolute right-8 top-10 h-[28rem] w-[20rem] rounded-t-full border-[18px] border-emerald-950/75 opacity-80"></div>
                            <div class="absolute bottom-10 right-12 h-44 w-28 rounded-t-full bg-white/70 shadow-2xl ring-1 ring-amber-900/10"></div>
                            <div class="absolute bottom-0 right-0 h-52 w-80 bg-gradient-to-t from-stone-900/15 to-transparent"></div>

                            <div class="absolute left-1/2 top-9 w-[17rem] -translate-x-1/2 rounded-[2.3rem] border-[10px] border-stone-950 bg-stone-950 p-2 shadow-[0_35px_90px_rgba(0,0,0,0.35)] sm:w-[19rem]">
                                <div class="overflow-hidden rounded-[1.7rem] bg-[#fbfaf5]">
                                    <div class="flex items-center justify-between px-5 py-4">
                                        <div>
                                            <p class="text-xs font-semibold text-stone-500">Assalamu</p>
                                            <p class="text-sm font-extrabold text-stone-950">Arsalan</p>
                                        </div>
                                        <span class="h-8 w-8 rounded-full border border-stone-200 bg-white"></span>
                                    </div>

                                    <div class="mx-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                                        <p class="text-sm font-extrabold">Hajj 2027</p>
                                        <div class="mt-3 h-2 rounded-full bg-stone-100">
                                            <div class="h-2 w-2/3 rounded-full bg-emerald-800"></div>
                                        </div>
                                        <div class="mt-3 flex justify-between text-[10px] font-semibold text-stone-500">
                                            <span>32 duas</span>
                                            <span>20 complete</span>
                                        </div>
                                    </div>

                                    <div class="px-4 py-5">
                                        <p class="text-xs font-extrabold text-stone-900">Categories</p>
                                        <div class="mt-3 grid grid-cols-4 gap-2">
                                            @foreach (['Trip', 'Family', 'Health', 'Peace'] as $category)
                                                <div class="rounded-xl bg-emerald-50 px-2 py-3 text-center text-[10px] font-bold text-emerald-900">{{ $category }}</div>
                                            @endforeach
                                        </div>

                                        <div class="mt-5 space-y-3">
                                            @foreach (['Dua for Safe Travel', 'Dua for Parents', 'Dua for Forgiveness'] as $dua)
                                                <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-stone-100">
                                                    <span class="h-3 w-3 rounded-full bg-emerald-700"></span>
                                                    <span class="text-xs font-bold">{{ $dua }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-1/2 h-28 w-[120vw] -translate-x-1/2 translate-y-1/2 rounded-[50%] border-t-[10px] border-emerald-950 bg-[#fbfaf5]"></div>
                    <div class="absolute bottom-0 left-1/2 z-10 flex h-8 w-8 -translate-x-1/2 translate-y-4 rotate-45 items-center justify-center border border-amber-300 bg-[#fbfaf5] text-amber-500">
                        <span class="-rotate-45 text-xs">✦</span>
                    </div>
                </section>

                <section id="features" class="relative px-5 pb-14 pt-24 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-4 md:grid-cols-3 lg:grid-cols-6">
                        @foreach ($features as [$title, $description])
                            <article class="border-r border-emerald-950/10 px-4 py-5 text-center last:border-r-0">
                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900 ring-1 ring-emerald-900/10">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </div>
                                <h2 class="mt-4 text-sm font-extrabold text-stone-950">{{ $title }}</h2>
                                <p class="mt-2 text-xs leading-5 text-stone-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="how-it-works" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="text-center">
                            <h2 class="font-serif text-3xl font-bold text-stone-950">How It Works</h2>
                            <div class="mx-auto mt-2 h-px w-28 bg-amber-300"></div>
                        </div>

                        <div class="mt-10 grid gap-6 lg:grid-cols-4">
                            @foreach ($steps as $index => [$title, $description])
                                <article class="relative rounded-2xl border border-emerald-950/10 bg-white p-7 text-center shadow-[0_18px_60px_rgba(15,23,42,0.06)]">
                                    <span class="absolute right-6 top-6 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-900 text-xs font-bold text-white">{{ $index + 1 }}</span>
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M6 5h12v14H6V5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                            <path d="M9 9h6M9 13h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <h3 class="mt-5 text-base font-extrabold text-stone-950">{{ $title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $description }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="blog" class="px-5 py-12 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                            <h2 class="font-serif text-3xl font-bold text-stone-950">From the Blog</h2>
                            <div class="flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full bg-emerald-900 px-4 py-2 text-white">All Posts</span>
                                <span class="rounded-full bg-white px-4 py-2 text-stone-700 ring-1 ring-stone-200">Reminders</span>
                                <span class="rounded-full bg-white px-4 py-2 text-stone-700 ring-1 ring-stone-200">Dua Guides</span>
                                <span class="rounded-full bg-white px-4 py-2 text-stone-700 ring-1 ring-stone-200">Quran & Hadith</span>
                                <a href="#resources" class="rounded-full px-4 py-2 text-emerald-900">View all →</a>
                            </div>
                        </div>

                        <div class="mt-7 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($posts as [$label, $title, $date, $readTime, $tone])
                                <article class="overflow-hidden rounded-xl border border-emerald-950/10 bg-white shadow-[0_18px_55px_rgba(15,23,42,0.06)]">
                                    <div class="relative h-44 bg-gradient-to-br {{ $tone }}">
                                        <span class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1 text-[11px] font-bold text-emerald-950 shadow-sm">{{ $label }}</span>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="text-lg font-extrabold leading-snug text-stone-950">{{ $title }}</h3>
                                        <p class="mt-3 text-xs font-semibold text-stone-500">{{ $date }} <span class="mx-2">•</span> {{ $readTime }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-8 text-center">
                            <a href="#resources" class="inline-flex items-center justify-center rounded-xl border border-emerald-950/20 bg-white px-5 py-2.5 text-sm font-bold text-emerald-950 shadow-sm">
                                Explore all articles
                                <span class="ml-2" aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </section>

                <section id="pricing" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="text-center">
                            <h2 class="font-serif text-3xl font-bold text-stone-950">Simple, Transparent Pricing</h2>
                            <p class="mt-2 text-sm text-stone-600">Choose the plan that works for you.</p>
                        </div>

                        <div class="mt-8 grid gap-6 lg:grid-cols-3">
                            @foreach ($plans as [$name, $description, $price, $items, $cta, $featured])
                                <article @class([
                                    'relative rounded-2xl border bg-white p-7 text-center shadow-[0_18px_60px_rgba(15,23,42,0.07)]',
                                    'border-emerald-800 ring-4 ring-amber-100' => $featured,
                                    'border-emerald-950/10' => ! $featured,
                                ])>
                                    @if ($featured)
                                        <span class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300 px-4 py-1 text-xs font-extrabold text-emerald-950">Most Popular</span>
                                    @endif
                                    <h3 class="text-xl font-extrabold">{{ $name }}</h3>
                                    <p class="mt-1 text-sm text-stone-500">{{ $description }}</p>
                                    <div class="mt-5">
                                        <span class="text-4xl font-extrabold">{{ $price }}</span>
                                        @if ($price !== '$0')
                                            <span class="text-sm font-semibold text-stone-500">/ month</span>
                                        @endif
                                    </div>
                                    <ul class="mt-6 space-y-3 text-left text-sm text-stone-700">
                                        @foreach ($items as $item)
                                            <li class="flex gap-3">
                                                <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] text-emerald-900">✓</span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('onboarding.start') }}" @class([
                                        'mt-7 inline-flex w-full items-center justify-center rounded-xl px-5 py-3 text-sm font-bold transition',
                                        'bg-emerald-900 text-white hover:bg-emerald-800' => $featured,
                                        'border border-emerald-950/20 bg-white text-emerald-950 hover:border-emerald-800' => ! $featured,
                                    ])>{{ $cta }}</a>
                                    <p class="mt-3 text-xs text-stone-500">Cancel anytime</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="resources" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-6 rounded-2xl bg-emerald-950 p-6 text-white shadow-[0_28px_90px_rgba(2,44,34,0.22)] lg:grid-cols-[1fr_auto] lg:items-center">
                        <div class="grid gap-5 sm:grid-cols-[12rem_1fr] sm:items-center">
                            <div class="h-32 rounded-xl bg-[radial-gradient(circle_at_70%_35%,rgba(251,191,36,0.65),transparent_18%),linear-gradient(135deg,#f5d38a,#064e3b)] shadow-inner"></div>
                            <div>
                                <h2 class="font-serif text-2xl font-bold">Stay inspired with daily reminders and new duas, delivered to your inbox.</h2>
                                <form class="mt-5 flex max-w-xl flex-col gap-3 sm:flex-row">
                                    <input type="email" placeholder="Enter your email address" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-white px-4 py-3 text-sm text-stone-900 outline-none placeholder:text-stone-400">
                                    <button type="button" class="rounded-xl border border-white/30 px-5 py-3 text-sm font-bold text-white transition hover:bg-white hover:text-emerald-950">Subscribe Now</button>
                                </form>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center text-xs font-bold">
                            <div class="rounded-xl bg-white/10 p-4">Weekly Reminders</div>
                            <div class="rounded-xl bg-white/10 p-4">New Duas & Articles</div>
                            <div class="rounded-xl bg-white/10 p-4">Special Updates</div>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="px-5 pb-10 pt-4 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-8 border-t border-emerald-950/10 pt-8 md:grid-cols-[1.3fr_repeat(4,1fr)]">
                    <div>
                        <x-home.logo />
                        <p class="mt-4 max-w-xs text-sm leading-6 text-stone-600">Your companion for collecting, organizing and sharing duas with ease.</p>
                        <p class="mt-4 text-xs font-semibold text-stone-500">© {{ now()->year }} My Dua List. All rights reserved.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold">Product</h3>
                        <div class="mt-3 grid gap-2 text-sm text-stone-600">
                            <a href="#features">Features</a>
                            <a href="#pricing">Pricing</a>
                            <a href="#how-it-works">How It Works</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold">Resources</h3>
                        <div class="mt-3 grid gap-2 text-sm text-stone-600">
                            <a href="#blog">Blog</a>
                            <a href="#resources">Dua Resources</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold">Company</h3>
                        <div class="mt-3 grid gap-2 text-sm text-stone-600">
                            <a href="{{ route('login') }}">Login</a>
                            <a href="{{ route('onboarding.start') }}">Create List</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold">Take My Dua List anywhere</h3>
                        <div class="mt-3 grid gap-2">
                            <span class="rounded-lg bg-stone-950 px-3 py-2 text-xs font-bold text-white">App Store</span>
                            <span class="rounded-lg bg-stone-950 px-3 py-2 text-xs font-bold text-white">Google Play</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
