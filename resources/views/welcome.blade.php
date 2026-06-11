@php
    $blogCategories = $blogCategories ?? collect();
    $homepagePosts = $homepagePosts ?? collect();

    $sliderImage = asset('images/hero/pilgrim.png');

    $sliderPositions = [
        'center 15%',
        'center 35%',
        'center 55%',
        'center 75%',
        '30% center',
        '70% center',
        'center center',
        'center 45%',
    ];

    $allInOneFeatures = [
        ['Share on Social Media', 'Share your list link on WhatsApp, Instagram, and more.'],
        ['Download Your List', 'Export your dua list anytime as a PDF.'],
        ['Privacy Controls', 'Choose who can view and submit to your list.'],
        ['Real-time Updates', 'See new dua requests as they come in.'],
        ['Completion Tracking', 'Mark duas complete and notify requesters instantly.'],
        ['Multi-device Access', 'Access your lists on phone, tablet, or desktop.'],
    ];

    $plans = [
        ['Free Forever', 'Ideal for casual users getting started.', '£0', null, ['2 Dua Lists', '25 Dua Requests / List', 'Up to 5 duas per user', 'Contains ads', 'Standard Support'], 'Get started for free', false],
        ['1 Unlimited List', 'Unlimited dua requests on any one list', '£7.99', 'one-time', ['Unlimited requests on one list', 'No dua limits per user', 'Contains ads', 'Premium Support'], 'Sign up', false],
        ['Unlimited Forever', 'Unlimited requests and unlimited lists for a lifetime', '£12.99', 'one-time', ['Unlimited Dua Requests', 'Unlimited Dua Lists', 'No limit on duas per user', 'Ad-free experience', 'Premium Support'], 'Sign up', true],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="The easiest way to collect dua requests for Hajj, Umrah, and every occasion.">

        <title>My Dua List - The easiest way to collect dua requests</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-stone-900 antialiased">
        <div class="min-h-screen overflow-x-hidden">
            @include('partials.marketing-header')

            <main id="home">
                {{-- Hero --}}
                <section class="relative overflow-hidden bg-white pb-8 pt-14 sm:pt-20">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(16,185,129,0.08),transparent_50%)]"></div>

                    <div class="relative mx-auto max-w-5xl px-5 text-center sm:px-6 lg:px-8">
                        <div class="animate-fade-in-up">
                            <h1 class="text-4xl font-extrabold leading-[1.08] tracking-tight text-emerald-900 sm:text-5xl lg:text-6xl xl:text-7xl">
                                The easiest way to<br>collect dua requests
                            </h1>

                            @php
                                $occasionLabels = [
                                    'For Hajj & Umrah',
                                    'For Ramadan',
                                    'for traveling',
                                    'For Hajj & Umrah',
                                ];
                            @endphp

                            <div class="hero-occasion mt-8 animate-fade-in-up" style="animation-delay: 0.25s">
                                <img src="{{ asset('images/line.png') }}" alt="" class="hero-occasion-line" aria-hidden="true">
                                <div class="hero-occasion-viewport" aria-live="polite">
                                    <div class="hero-occasion-track">
                                        @foreach ($occasionLabels as $label)
                                            <div class="hero-occasion-item">
                                                <img src="{{ asset('images/cube-icon.png') }}" alt="" aria-hidden="true">
                                                <span>{{ $label }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <img src="{{ asset('images/line.png') }}" alt="" class="hero-occasion-line" aria-hidden="true">
                            </div>

                            <div class="mt-8">
                                <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-800/25 transition hover:scale-[1.03] hover:bg-emerald-700">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Animated image slider --}}
                    <div class="hero-slider mt-10 sm:mt-14">
                        <div class="hero-slider-track" aria-hidden="true">
                            @foreach (array_merge($sliderPositions, $sliderPositions) as $position)
                                <div
                                    class="hero-slider-item"
                                    style="background-image: url('{{ $sliderImage }}'); background-position: {{ $position }};"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Social proof --}}
                <section class="px-5 py-16 text-center sm:px-6 lg:px-8">
                    <p class="reveal-on-scroll text-2xl font-extrabold text-emerald-800 sm:text-3xl">
                        Over 55,875 Duas Completed
                    </p>
                    <h2 class="reveal-on-scroll stagger-1 mx-auto mt-4 max-w-2xl text-3xl font-extrabold tracking-tight text-stone-950 sm:text-4xl lg:text-5xl">
                        All your duas in one place, forever.
                    </h2>
                </section>

                {{-- Alternating feature showcases --}}
                <section id="how-it-works" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-6xl space-y-24 lg:space-y-32">
                        {{-- Feature 1 --}}
                        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            <div class="feature-fade feature-fade-left order-2 stagger-1 lg:order-1">
                                <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-[0_30px_80px_rgba(2,44,34,0.08)] sm:p-8">
                                    <div class="rounded-2xl bg-white p-5 shadow-lg ring-1 ring-emerald-950/5">
                                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Zain's Umrah Dua List</p>
                                        <p class="mt-2 text-sm font-semibold text-stone-500">mydualist.com/zains-umrah</p>
                                        <div class="mt-5 flex flex-wrap gap-2">
                                            @foreach (['WhatsApp', 'Instagram', 'X', 'Facebook'] as $platform)
                                                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-100">{{ $platform }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="feature-fade feature-fade-right order-1 stagger-2 lg:order-2">
                                <h3 class="text-3xl font-extrabold tracking-tight text-emerald-900 sm:text-4xl">Fast and simple Dua Collection</h3>
                                <p class="mt-5 text-lg leading-8 text-stone-600">
                                    Create a list in minutes, share your unique link, and let family and friends submit their dua requests — no app download required.
                                </p>
                            </div>
                        </div>

                        {{-- Feature 2 --}}
                        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            <div class="feature-fade feature-fade-left stagger-1">
                                <h3 class="text-3xl font-extrabold tracking-tight text-emerald-900 sm:text-4xl">Complete Dua Effortlessly</h3>
                                <p class="mt-5 text-lg leading-8 text-stone-600">
                                    See every request in one calm dashboard. Mark duas complete with a tap and keep track of your spiritual commitments.
                                </p>
                            </div>
                            <div class="feature-fade feature-fade-right stagger-2">
                                <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-[0_30px_80px_rgba(2,44,34,0.08)] sm:p-8">
                                    <div class="space-y-3">
                                        @foreach ([['Tawaf entrance', true], ['Safa & Marwa', true], ['After Asr prayer', false]] as [$label, $done])
                                            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-emerald-950/5">
                                                <span @class([
                                                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                                    'bg-emerald-800 text-white' => $done,
                                                    'border-2 border-emerald-200 text-transparent' => ! $done,
                                                ])>{{ $done ? '✓' : '' }}</span>
                                                <span class="text-sm font-semibold text-stone-800">{{ $label }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Feature 3 --}}
                        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            <div class="feature-fade feature-fade-left order-2 stagger-1 lg:order-1">
                                <div class="mx-auto max-w-xs">
                                    <div class="rounded-[2.5rem] border-8 border-stone-900 bg-stone-900 p-2 shadow-2xl">
                                        <div class="overflow-hidden rounded-[1.75rem] bg-stone-100">
                                            <div class="bg-stone-200 px-6 py-2 text-center text-[10px] font-semibold text-stone-500">9:41</div>
                                            <div class="bg-gradient-to-b from-emerald-800 to-emerald-950 px-5 py-8 text-white">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-200">My Dua List</p>
                                                <p class="mt-2 text-sm font-bold leading-snug">Your dua for health has been completed 🤲</p>
                                                <p class="mt-1 text-xs text-emerald-100/80">Zain marked your request as done</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="feature-fade feature-fade-right order-1 stagger-2 lg:order-2">
                                <h3 class="text-3xl font-extrabold tracking-tight text-emerald-900 sm:text-4xl">Instant Dua Completion Notifications</h3>
                                <p class="mt-5 text-lg leading-8 text-stone-600">
                                    When you complete a dua, the person who requested it is notified instantly — bringing peace and connection across distances.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="reveal-on-scroll mt-16 text-center">
                        <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-800/25 transition hover:scale-[1.03] hover:bg-emerald-700">
                            Get started for free
                        </a>
                    </div>
                </section>

                {{-- All-in-one grid --}}
                <section id="features" class="bg-stone-50 px-5 py-20 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-6xl">
                        <x-home.section-heading
                            title="All-in-One Dua Management"
                            description="Everything you need to collect, organize, and complete duas — in one beautiful app."
                            class="reveal-on-scroll"
                        />

                        <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($allInOneFeatures as $index => [$title, $description])
                                <article @class(['reveal-on-scroll rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md', 'stagger-'.(($index % 3) + 1)])>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-800 text-white">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-lg font-extrabold text-stone-950">{{ $title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $description }}</p>
                                </article>
                            @endforeach
                        </div>

                        <div class="reveal-on-scroll mt-12 text-center">
                            <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-800/25 transition hover:bg-emerald-700">
                                Start for free
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Pricing --}}
                <section id="pricing" class="px-5 py-20 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-6xl">
                        <x-home.section-heading
                            title="Start for free, or upgrade to unlock more features"
                            description="Free users get 2 Dua lists each with a maximum of 25 dua requests."
                            class="reveal-on-scroll"
                        />

                        <div class="mt-12 grid gap-6 lg:grid-cols-3">
                            @foreach ($plans as $index => [$name, $description, $price, $period, $items, $cta, $featured])
                                <article @class([
                                    'reveal-on-scroll relative rounded-3xl border bg-white p-8 text-center shadow-[0_22px_70px_rgba(15,23,42,0.06)]',
                                    'border-emerald-700 ring-4 ring-emerald-100' => $featured,
                                    'border-emerald-100' => ! $featured,
                                    'stagger-'.($index + 1),
                                ])>
                                    @if ($featured)
                                        <span class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300 px-4 py-1 text-xs font-extrabold text-emerald-950">Popular</span>
                                    @endif
                                    <h3 class="text-xl font-extrabold text-stone-950">{{ $name }}</h3>
                                    <p class="mt-2 text-sm text-stone-500">{{ $description }}</p>
                                    <div class="mt-6">
                                        <span class="text-5xl font-extrabold text-stone-950">{{ $price }}</span>
                                        @if ($period)
                                            <span class="text-sm font-semibold text-stone-500"> {{ $period }}</span>
                                        @endif
                                    </div>
                                    <ul class="mt-8 space-y-3 text-left text-sm text-stone-700">
                                        @foreach ($items as $item)
                                            <li class="flex gap-3">
                                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-800">✓</span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('onboarding.start') }}" @class([
                                        'mt-8 inline-flex w-full items-center justify-center rounded-full px-5 py-3.5 text-sm font-bold transition',
                                        'bg-emerald-800 text-white hover:bg-emerald-700' => $featured,
                                        'border-2 border-emerald-800 text-emerald-800 hover:bg-emerald-50' => ! $featured,
                                    ])>{{ $cta }}</a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Dua Resources / Blog --}}
                <section id="blog" class="bg-stone-50 px-5 py-20 sm:px-6 lg:px-8" x-data="{ activeCategory: 'all' }">
                    <div class="mx-auto max-w-6xl">
                        <x-home.section-heading title="Dua Resources" class="reveal-on-scroll" />

                        <div class="reveal-on-scroll mt-8 flex flex-wrap justify-center gap-2">
                            <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-emerald-800 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:ring-emerald-200'" class="rounded-full px-5 py-2 text-sm font-bold transition">All</button>
                            @foreach ($blogCategories as $category)
                                <button type="button" @click="activeCategory = '{{ $category->slug }}'" :class="activeCategory === '{{ $category->slug }}' ? 'bg-emerald-800 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:ring-emerald-200'" class="rounded-full px-5 py-2 text-sm font-bold transition">{{ $category->name }}</button>
                            @endforeach
                        </div>

                        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @forelse ($homepagePosts->take(6) as $post)
                                <article
                                    x-show="activeCategory === 'all' || activeCategory === '{{ $post->category->slug }}'"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                                >
                                    <a href="{{ route('blogs.show', $post->slug) }}" class="block">
                                        <div class="relative h-48 overflow-hidden">
                                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="h-full w-full max-w-none object-cover transition duration-500 hover:scale-105">
                                            <span class="absolute bottom-3 left-3 rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-800 shadow-sm">{{ $post->category->name }}</span>
                                        </div>
                                        <div class="p-5">
                                            <h3 class="text-lg font-extrabold leading-snug text-stone-950">{{ $post->title }}</h3>
                                            <p class="mt-2 line-clamp-2 text-sm text-stone-600">{{ $post->excerpt }}</p>
                                        </div>
                                    </a>
                                </article>
                            @empty
                                <p class="col-span-full rounded-2xl bg-white p-8 text-center text-stone-600 ring-1 ring-stone-200">Blog articles will appear here once published.</p>
                            @endforelse
                        </div>

                        <div class="reveal-on-scroll mt-10 text-center">
                            <a href="{{ route('blogs.index') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-800/25 transition hover:bg-emerald-700">
                                Read more
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Bottom CTA banner --}}
                <section class="px-5 py-10 sm:px-6 lg:px-8">
                    <a href="{{ route('onboarding.start') }}" class="reveal-on-scroll group mx-auto flex max-w-6xl items-center justify-between gap-6 rounded-2xl bg-emerald-950 px-8 py-8 text-white shadow-xl transition hover:bg-emerald-900 sm:px-12">
                        <p class="text-xl font-extrabold leading-snug sm:text-2xl">
                            Going for Umrah? Build your own list of duas to complete for others
                        </p>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/10 text-2xl transition group-hover:translate-x-1 group-hover:bg-white/20" aria-hidden="true">→</span>
                    </a>
                </section>
            </main>

            @include('partials.marketing-footer')
        </div>
    </body>
</html>
