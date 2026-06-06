<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Create a private dua list, share it with loved ones, and collect prayer requests in one peaceful place.">

        <title>My Dua List - Collect Dua Requests Beautifully</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf7] font-sans text-stone-900 antialiased">
        <div class="min-h-screen overflow-hidden">
            <header
                class="relative z-50 border-b border-emerald-950/5 bg-[#fbfaf7]/85 backdrop-blur"
                x-data="{ open: false }"
            >
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
                    <x-home.logo />

                    <nav class="hidden items-center gap-3 md:flex" aria-label="Main navigation">
                        <x-home.button href="{{ url('/admin/login') }}" variant="ghost" size="sm">Sign In</x-home.button>
                        <x-home.button href="#pricing" variant="secondary" size="sm">Create Dua List</x-home.button>
                        <x-home.button href="#submit-dua" variant="primary" size="sm">Submit a Dua</x-home.button>
                    </nav>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-950/10 bg-white text-emerald-950 shadow-sm md:hidden"
                        aria-label="Open navigation"
                        x-on:click="open = ! open"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <div class="border-t border-emerald-950/5 bg-white px-5 py-4 shadow-xl shadow-emerald-950/5 md:hidden" x-cloak x-show="open">
                    <div class="mx-auto grid max-w-7xl gap-3">
                        <x-home.button href="{{ url('/admin/login') }}" variant="ghost" size="sm" class="justify-start">Sign In</x-home.button>
                        <x-home.button href="#pricing" variant="secondary" size="sm">Create Dua List</x-home.button>
                        <x-home.button href="#submit-dua" variant="primary" size="sm">Submit a Dua</x-home.button>
                    </div>
                </div>
            </header>

            <main>
                <section class="relative">
                    <div class="absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[radial-gradient(circle_at_30%_20%,rgba(16,185,129,0.16),transparent_34%),radial-gradient(circle_at_80%_12%,rgba(20,83,45,0.12),transparent_32%)]"></div>

                    <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-16 sm:px-6 lg:grid-cols-[1.03fr_0.97fr] lg:px-8 lg:pb-28 lg:pt-24">
                        <div class="max-w-3xl">
                            <p class="inline-flex items-center gap-2 rounded-full border border-emerald-950/10 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                100% private dua lists for families and communities
                            </p>

                            <h1 class="mt-7 max-w-2xl text-5xl font-extrabold tracking-tight text-stone-950 sm:text-6xl lg:text-7xl">
                                The easiest way to <span class="text-emerald-700">collect dua requests</span>
                            </h1>

                            <p class="mt-6 max-w-xl text-lg leading-8 text-stone-600">
                                Create a beautiful dua list, share it with loved ones, and collect prayer requests in one calm, private place.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <x-home.button href="#pricing" size="lg">Create your list</x-home.button>
                                <x-home.button href="#how-it-works" variant="secondary" size="lg">
                                    See how it works
                                    <span aria-hidden="true">→</span>
                                </x-home.button>
                            </div>

                            <div class="mt-8 grid gap-4 text-sm font-semibold text-stone-700 sm:grid-cols-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800">✓</span>
                                    100% Private
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800">✓</span>
                                    Easy to Use
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800">✓</span>
                                    Share Anywhere
                                </div>
                            </div>

                            <div class="mt-10 flex flex-wrap items-center gap-4">
                                <div class="flex -space-x-3">
                                    @foreach (['bg-emerald-200', 'bg-stone-300', 'bg-amber-200', 'bg-sky-200', 'bg-rose-200'] as $color)
                                        <span class="h-10 w-10 rounded-full border-2 border-white {{ $color }}"></span>
                                    @endforeach
                                </div>
                                <p class="text-sm text-stone-600">
                                    <span class="font-bold text-amber-500">★★★★★</span>
                                    Trusted by families, masjids and small communities
                                </p>
                            </div>
                        </div>

                        <div class="relative mx-auto h-[31rem] w-full max-w-xl sm:h-[35rem]">
                            <div class="absolute left-[4%] top-24 h-56 w-28 rotate-[-6deg] rounded-[2rem] bg-gradient-to-br from-stone-200 via-amber-100 to-stone-50 shadow-2xl shadow-stone-900/10 ring-1 ring-white/80 sm:h-64 sm:w-32"></div>
                            <div class="absolute left-[22%] top-14 h-64 w-32 rotate-[4deg] rounded-[2rem] bg-gradient-to-br from-sky-200 via-white to-emerald-100 shadow-2xl shadow-stone-900/10 ring-1 ring-white/80 sm:h-72 sm:w-36"></div>
                            <div class="absolute left-[43%] top-0 h-[22rem] w-40 rounded-[2.2rem] bg-gradient-to-br from-stone-950 via-stone-800 to-emerald-900 p-3 shadow-2xl shadow-emerald-950/20 ring-1 ring-white/80 sm:h-[25rem] sm:w-48">
                                <div class="h-full rounded-[1.6rem] bg-[radial-gradient(circle_at_50%_20%,rgba(255,255,255,0.35),transparent_20%),linear-gradient(135deg,#2f2a25,#0f3d33)]"></div>
                            </div>
                            <div class="absolute right-[15%] top-20 h-60 w-28 rotate-[5deg] rounded-[2rem] bg-gradient-to-br from-indigo-100 via-sky-100 to-stone-50 shadow-2xl shadow-stone-900/10 ring-1 ring-white/80 sm:h-72 sm:w-36"></div>
                            <div class="absolute right-0 top-28 h-52 w-28 rotate-[4deg] rounded-[1.8rem] bg-gradient-to-br from-amber-200 via-emerald-100 to-stone-50 shadow-2xl shadow-stone-900/10 ring-1 ring-white/80 sm:h-60 sm:w-32"></div>
                            <div class="absolute right-[17%] top-7 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-900/20">
                                ✓
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border-y border-emerald-950/5 bg-white/70 py-8">
                    <div class="mx-auto max-w-7xl px-5 text-center sm:px-6 lg:px-8">
                        <p class="text-sm font-medium text-stone-500">Loved by individuals, families and communities</p>
                        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4 text-sm font-bold text-stone-700">
                            <span>Google</span>
                            <span>facebook</span>
                            <span>YouTube</span>
                            <span>Instagram</span>
                            <span>TikTok</span>
                            <span>LinkedIn</span>
                        </div>
                    </div>
                </section>

                <section id="submit-dua" class="py-20 sm:py-24">
                    <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 sm:px-6 lg:grid-cols-2 lg:px-8">
                        <div class="rounded-[2rem] border border-emerald-950/10 bg-emerald-50/70 p-6 shadow-[0_24px_90px_rgba(15,23,42,0.07)] sm:p-8">
                            <div class="flex items-start justify-between gap-5">
                                <div>
                                    <h2 class="text-2xl font-bold tracking-tight text-stone-950">Share your list anywhere</h2>
                                    <p class="mt-3 max-w-md text-sm leading-6 text-stone-600">
                                        Create a private link and share it with your friends, family or community. Collect requests with complete privacy.
                                    </p>
                                </div>
                                <span class="hidden rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-800 shadow-sm sm:inline-flex">Private</span>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-5">
                                @foreach (['Copy link', 'WhatsApp', 'Facebook', 'Twitter', 'QR Code'] as $item)
                                    <div class="rounded-2xl border border-emerald-950/10 bg-white p-4 text-center text-xs font-semibold text-stone-700 shadow-sm">
                                        <div class="mx-auto mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">↗</div>
                                        {{ $item }}
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 flex flex-wrap gap-3 text-xs font-semibold text-emerald-800">
                                <span class="rounded-full bg-white px-3 py-1 shadow-sm">324 requests</span>
                                <span class="rounded-full bg-white px-3 py-1 shadow-sm">68 prayers</span>
                                <span class="rounded-full bg-white px-3 py-1 shadow-sm">12 answered</span>
                            </div>
                        </div>

                        <div class="relative mx-auto w-full max-w-md rounded-[2.4rem] border-[10px] border-stone-950 bg-white p-5 shadow-[0_28px_90px_rgba(15,23,42,0.18)]">
                            <div class="mx-auto mb-4 h-5 w-28 rounded-full bg-stone-950"></div>
                            <div class="rounded-[1.6rem] bg-[#fbfaf7] p-6">
                                <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Private form</p>
                                <h3 class="mt-2 text-center text-xl font-bold text-stone-950">Submit a dua request</h3>
                                <p class="mt-2 text-center text-sm text-stone-500">Your request will stay private.</p>

                                <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 text-sm text-stone-400">
                                    Write your request here...
                                </div>

                                <label class="mt-4 flex items-center gap-2 text-xs text-stone-600">
                                    <span class="h-4 w-4 rounded border border-stone-300 bg-white"></span>
                                    Keep this anonymous
                                </label>

                                <button type="button" class="mt-5 w-full rounded-xl bg-emerald-800 px-4 py-3 text-sm font-bold text-white shadow-sm">
                                    Submit request
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pb-20 sm:pb-24">
                    <div class="mx-auto grid max-w-6xl gap-5 px-5 sm:px-6 md:grid-cols-3 lg:px-8">
                        <x-home.feature-card
                            icon="lock"
                            title="Private & Secure"
                            description="Your requests stay private, protected and visible only to the people you invite."
                            :items="['No sign-up required for guests', 'Anonymous submissions', 'Spam protection', 'Secure sharing']"
                        />
                        <x-home.feature-card
                            title="Mark answered"
                            description="Keep track of requests and gently celebrate when duas are answered."
                            :items="['Mark as answered', 'Add status updates', 'Private notes', 'Mobile friendly']"
                        />
                        <x-home.feature-card
                            icon="bell"
                            title="Instant notifications"
                            description="Get notified when someone submits or marks a dua as answered."
                            :items="['WhatsApp notifications', 'Email alerts', 'Real-time updates', 'Never miss a prayer']"
                        />
                    </div>
                </section>

                <section id="how-it-works" class="bg-white py-20 sm:py-24">
                    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                        <x-home.section-heading
                            title="How it works"
                            description="Collecting dua requests has never been easier."
                        />

                        <div class="relative mt-14 grid gap-5 md:grid-cols-5">
                            <div class="absolute left-8 right-8 top-9 hidden h-px bg-emerald-950/10 md:block"></div>
                            @foreach ([
                                ['Create your list', 'Set up a private space.'],
                                ['Share your link', 'Invite loved ones.'],
                                ['Collect requests', 'People submit their duas.'],
                                ['Make dua', 'Pray for the requests.'],
                                ['Mark answered', 'Update and inspire others.'],
                            ] as $index => [$title, $text])
                                <div class="relative rounded-3xl border border-emerald-950/10 bg-[#fbfaf7] p-5 text-center shadow-sm">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-lg font-bold text-emerald-800 shadow-sm">
                                        {{ $index + 1 }}
                                    </div>
                                    <h3 class="mt-5 text-base font-bold text-stone-950">{{ $title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $text }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="pricing" class="py-20 sm:py-24">
                    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                        <x-home.section-heading
                            title="Simple, transparent pricing"
                            description="Choose the plan that works best for you."
                        />

                        <div class="mt-12 grid gap-6 lg:grid-cols-3">
                            <x-home.pricing-card
                                name="Free Forever"
                                price="£0"
                                description="Perfect for trying out My Dua List."
                                :features="['One dua list', 'Up to 25 requests', 'Easy list sharing', 'Standard support']"
                                cta="Get started for free"
                            />
                            <x-home.pricing-card
                                name="Unlimited List"
                                price="£79"
                                period="/ year"
                                description="Unlimited usage with a yearly plan."
                                :features="['Unlimited requests in one list', 'No ads', 'Custom list branding', 'Priority support']"
                                highlighted
                                badge="Most Popular"
                                cta="Start 7-day free trial"
                            />
                            <x-home.pricing-card
                                name="Unlimited Forever"
                                price="£129"
                                description="One-time payment for long-term use."
                                :features="['Unlimited dua requests', 'Unlimited list links', 'No ads, ever', 'Custom branding', 'Priority support']"
                                cta="Start 7-day free trial"
                            />
                        </div>

                        <p class="mt-7 text-center text-sm text-stone-500">Cancel anytime. No hidden fees.</p>
                    </div>
                </section>

                <section class="bg-white py-20 sm:py-24">
                    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <x-home.section-heading
                                align="left"
                                title="Dua resources & inspiration"
                                description="Articles, guides and stories to strengthen your connection with dua."
                            />
                            <x-home.button href="#" variant="secondary" size="sm">View all resources</x-home.button>
                        </div>

                        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <x-home.resource-card label="Guide" title="How to Make the Most of Your Dua List" description="Simple ways to collect and pray with intention." tone="emerald" />
                            <x-home.resource-card label="Tips" title="5 Tips to Get More People to Share" description="Encourage your community to submit requests." tone="stone" />
                            <x-home.resource-card label="Inspiration" title="The Power of Collective Dua" description="Why shared prayer can bring people together." tone="amber" />
                            <x-home.resource-card label="Feature" title="New: Advanced Analytics for Pro Users" description="Understand engagement without losing privacy." tone="sky" />
                        </div>
                    </div>
                </section>

                <section class="px-5 py-12 sm:px-6 lg:px-8">
                    <div class="mx-auto flex max-w-6xl flex-col gap-6 rounded-[2rem] bg-emerald-950 p-8 text-white shadow-[0_30px_90px_rgba(2,44,34,0.22)] sm:flex-row sm:items-center sm:justify-between sm:p-10">
                        <div class="flex gap-5">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl">✦</div>
                            <div>
                                <h2 class="text-2xl font-bold">Ready to start collecting dua requests?</h2>
                                <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-50/80">
                                    Join families and communities making dua together in a private, beautiful way.
                                </p>
                            </div>
                        </div>
                        <x-home.button href="#pricing" variant="secondary" size="md" class="shrink-0">
                            Create your list now
                            <span aria-hidden="true">→</span>
                        </x-home.button>
                    </div>
                </section>
            </main>

            <footer class="bg-emerald-950 px-5 py-10 text-emerald-50 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-6xl flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <x-home.logo class="text-white [&_span:last-child]:text-white [&_span:first-child]:border-white/10 [&_span:first-child]:bg-white/10 [&_span:first-child]:text-white" />
                        <p class="mt-4 max-w-md text-sm leading-6 text-emerald-50/70">
                            The simplest way to collect dua requests and support each other through prayer.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm font-semibold text-emerald-50/80">
                        <a href="{{ url('/admin/login') }}" class="hover:text-white">Sign In</a>
                        <a href="#pricing" class="hover:text-white">Create Dua List</a>
                        <a href="#submit-dua" class="hover:text-white">Submit a Dua</a>
                    </div>
                </div>
                <div class="mx-auto mt-8 max-w-6xl border-t border-white/10 pt-6 text-sm text-emerald-50/60">
                    © {{ now()->year }} My Dua List. All rights reserved.
                </div>
            </footer>
        </div>
    </body>
</html>
