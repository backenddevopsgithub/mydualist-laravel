@php
    $blogCategories = $blogCategories ?? collect();
    $homepagePosts = $homepagePosts ?? collect();

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

    $plans = [
        ['Free Forever', 'Ideal for casual users getting started.', '£0', ['2 Dua Lists', '25 Dua Requests / List', 'Up to 5 duas per user', 'Contains ads', 'Standard Support'], 'Get started for free', false],
        ['1 Unlimited List', 'Unlimited dua requests on any one list', '£7.99', ['Unlimited requests on one list', 'No dua limits per user', 'Contains ads', 'Premium Support'], 'Sign up', false],
        ['Unlimited Forever', 'Unlimited requests and unlimited lists for a lifetime', '£12.99', ['Unlimited Dua Requests', 'Unlimited Dua Lists', 'No limit on duas per user', 'Ad-free experience', 'Premium Support'], 'Sign up', true],
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
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        <div class="min-h-screen overflow-hidden">
            @include('partials.marketing-header')

            <main id="home">
                <section class="relative bg-[#f7f4ea]">
                    <div class="absolute inset-0 bg-[url('https://www.mydualist.com/wp-content/uploads/2024/02/Texture.png')] bg-cover opacity-20"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_14%,rgba(16,185,129,0.12),transparent_28%),radial-gradient(circle_at_78%_18%,rgba(120,72,20,0.12),transparent_32%)]"></div>

                    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 pb-28 pt-16 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:pb-36 lg:pt-20">
                        <div class="max-w-2xl animate-fade-in-up">
                            <p class="inline-flex items-center gap-2 rounded-lg border border-amber-200/70 bg-white/80 px-4 py-2 text-base font-semibold text-emerald-900 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse-soft"></span>
                                Your Journey. Your Supplications. Your List.
                            </p>

                            <h1 class="mt-8 max-w-xl font-serif text-5xl font-bold leading-[1.02] tracking-tight text-stone-950 sm:text-6xl lg:text-7xl">
                                Collect. Organize. <span class="text-emerald-900">Pray. Share.</span>
                            </h1>

                            <p class="mt-6 max-w-xl text-xl leading-8 text-stone-700">
                                My Dua List helps you collect and organize your duas for every occasion in life. Keep them close, stay consistent, and share the blessings.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('onboarding.start') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-950 px-6 py-3.5 text-base font-bold text-white shadow-sm shadow-emerald-950/20 transition hover:scale-[1.02] hover:bg-emerald-800">
                                    Create My Dua List
                                    <span aria-hidden="true">→</span>
                                </a>
                                <a href="{{ route('blogs.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-950/20 bg-white/80 px-6 py-3.5 text-base font-bold text-emerald-950 shadow-sm transition hover:border-emerald-800">
                                    Explore Dua Resources
                                </a>
                            </div>
                        </div>

                        <div class="relative reveal-on-scroll stagger-2">
                            <div class="animate-float-soft overflow-hidden rounded-[2.5rem] shadow-[0_35px_120px_rgba(45,35,20,0.22)] ring-1 ring-emerald-950/10">
                                <img
                                    src="https://www.mydualist.com/wp-content/uploads/2024/01/Pilgrim-img.png"
                                    alt="Muslim pilgrim making dua"
                                    class="h-[34rem] w-full object-cover"
                                >
                            </div>
                            <div class="absolute -bottom-6 -left-4 rounded-2xl bg-white px-5 py-4 shadow-xl ring-1 ring-emerald-950/10">
                                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-800">Trusted by Muslims worldwide</p>
                                <p class="mt-1 text-2xl font-extrabold text-stone-950">50,000+ dua lists created</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="features" class="relative px-5 pb-14 pt-24 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-4 md:grid-cols-3 lg:grid-cols-6">
                        @foreach ($features as $index => [$title, $description])
                            <article @class(['reveal-on-scroll border-r border-emerald-950/10 px-4 py-5 text-center last:border-r-0', 'stagger-'.(($index % 4) + 1)])>
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900 ring-1 ring-emerald-900/10">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </div>
                                <h2 class="mt-4 text-lg font-extrabold text-stone-950">{{ $title }}</h2>
                                <p class="mt-2 text-base leading-7 text-stone-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="how-it-works" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl reveal-on-scroll">
                        <div class="text-center">
                            <h2 class="font-serif text-4xl font-bold text-stone-950">How It Works</h2>
                            <div class="mx-auto mt-2 h-px w-28 bg-amber-300"></div>
                        </div>

                        <div class="mt-10 grid gap-6 lg:grid-cols-4">
                            @foreach ($steps as $index => [$title, $description])
                                <article @class(['relative rounded-2xl border border-emerald-950/10 bg-white p-7 text-center shadow-[0_18px_60px_rgba(15,23,42,0.06)] reveal-on-scroll', 'stagger-'.($index + 1)])>
                                    <span class="absolute right-6 top-6 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-900 text-sm font-bold text-white">{{ $index + 1 }}</span>
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900">
                                        <img src="https://www.mydualist.com/wp-content/uploads/2024/02/Cube_icon.png" alt="" class="h-10 w-10 object-contain">
                                    </div>
                                    <h3 class="mt-5 text-xl font-extrabold text-stone-950">{{ $title }}</h3>
                                    <p class="mt-2 text-base leading-7 text-stone-600">{{ $description }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="blog" class="px-5 py-12 sm:px-6 lg:px-8" x-data="{ activeCategory: 'all' }">
                    <div class="mx-auto max-w-7xl reveal-on-scroll">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                            <h2 class="font-serif text-4xl font-bold text-stone-950">From the Blog</h2>
                            <div class="flex flex-wrap gap-2 text-sm font-bold">
                                <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'" class="rounded-full px-4 py-2 transition">All Posts</button>
                                @foreach ($blogCategories as $category)
                                    <button type="button" @click="activeCategory = '{{ $category->slug }}'" :class="activeCategory === '{{ $category->slug }}' ? 'bg-emerald-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'" class="rounded-full px-4 py-2 transition">{{ $category->name }}</button>
                                @endforeach
                                <a href="{{ route('blogs.index') }}" class="rounded-full px-4 py-2 text-emerald-900">View all →</a>
                            </div>
                        </div>

                        <div class="mt-7 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @forelse ($homepagePosts as $post)
                                <article
                                    x-show="activeCategory === 'all' || activeCategory === '{{ $post->category->slug }}'"
                                    x-cloak
                                    class="overflow-hidden rounded-2xl border border-emerald-950/10 bg-white shadow-[0_18px_55px_rgba(15,23,42,0.06)] transition hover:-translate-y-1 hover:shadow-xl"
                                >
                                    <a href="{{ route('blogs.show', $post->slug) }}" class="block">
                                        <div class="relative h-64 overflow-hidden">
                                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 hover:scale-105">
                                            <span class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1 text-sm font-bold text-emerald-950 shadow-sm">{{ $post->category->name }}</span>
                                        </div>
                                        <div class="p-6">
                                            <h3 class="text-xl font-extrabold leading-snug text-stone-950">{{ $post->title }}</h3>
                                            <p class="mt-3 line-clamp-2 text-base text-stone-600">{{ $post->excerpt }}</p>
                                            <p class="mt-4 text-sm font-semibold text-stone-500">{{ $post->published_at?->format('M j, Y') }} <span class="mx-2">•</span> {{ $post->read_time_minutes }} min read</p>
                                        </div>
                                    </a>
                                </article>
                            @empty
                                <p class="col-span-full rounded-2xl bg-white p-8 text-center text-base text-stone-600 ring-1 ring-stone-200">Blog articles will appear here once published in the admin panel.</p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section id="pricing" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl reveal-on-scroll">
                        <div class="text-center">
                            <h2 class="font-serif text-4xl font-bold text-stone-950">Start for free, or upgrade to unlock more features</h2>
                            <p class="mt-3 text-lg text-stone-600">Free users get 2 Dua lists each with a maximum of 25 dua requests.</p>
                        </div>

                        <div class="mt-8 grid gap-6 lg:grid-cols-3">
                            @foreach ($plans as [$name, $description, $price, $items, $cta, $featured])
                                <article @class([
                                    'relative rounded-2xl border bg-white p-7 text-center shadow-[0_18px_60px_rgba(15,23,42,0.07)] reveal-on-scroll',
                                    'border-emerald-800 ring-4 ring-amber-100' => $featured,
                                    'border-emerald-950/10' => ! $featured,
                                ])>
                                    @if ($featured)
                                        <span class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300 px-4 py-1 text-sm font-extrabold text-emerald-950">Popular</span>
                                    @endif
                                    <h3 class="text-2xl font-extrabold">{{ $name }}</h3>
                                    <p class="mt-2 text-base text-stone-500">{{ $description }}</p>
                                    <div class="mt-5">
                                        <span class="text-5xl font-extrabold">{{ $price }}</span>
                                        @if ($price !== '£0')
                                            <span class="text-base font-semibold text-stone-500"> one-time</span>
                                        @endif
                                    </div>
                                    <ul class="mt-6 space-y-3 text-left text-base text-stone-700">
                                        @foreach ($items as $item)
                                            <li class="flex gap-3">
                                                <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-900">✓</span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('onboarding.start') }}" @class([
                                        'mt-7 inline-flex w-full items-center justify-center rounded-xl px-5 py-3.5 text-base font-bold transition',
                                        'bg-emerald-900 text-white hover:bg-emerald-800' => $featured,
                                        'border border-emerald-950/20 bg-white text-emerald-950 hover:border-emerald-800' => ! $featured,
                                    ])>{{ $cta }}</a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="resources" class="px-5 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-6 rounded-2xl bg-emerald-950 p-6 text-white shadow-[0_28px_90px_rgba(2,44,34,0.22)] reveal-on-scroll lg:grid-cols-[1fr_auto] lg:items-center">
                        <div class="grid gap-5 sm:grid-cols-[12rem_1fr] sm:items-center">
                            <img src="https://www.mydualist.com/wp-content/uploads/2024/01/Pilgrim-img.png" alt="" class="h-32 w-full rounded-xl object-cover">
                            <div>
                                <h2 class="font-serif text-3xl font-bold">Stay inspired with daily reminders and new duas, delivered to your inbox.</h2>
                                @if (session('newsletter_status'))
                                    <p class="mt-5 rounded-xl bg-white/10 px-4 py-3 text-base font-semibold">{{ session('newsletter_status') }}</p>
                                @endif
                                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="mt-5 flex max-w-xl flex-col gap-3 sm:flex-row">
                                    @csrf
                                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email address" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-white px-4 py-3.5 text-base text-stone-900 outline-none placeholder:text-stone-400">
                                    <button type="submit" class="rounded-xl border border-white/30 px-5 py-3.5 text-base font-bold text-white transition hover:bg-white hover:text-emerald-950">Subscribe Now</button>
                                </form>
                                @error('email')
                                    <p class="mt-2 text-sm font-semibold text-amber-200">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center text-sm font-bold">
                            <div class="rounded-xl bg-white/10 p-4">Weekly Reminders</div>
                            <div class="rounded-xl bg-white/10 p-4">New Duas & Articles</div>
                            <div class="rounded-xl bg-white/10 p-4">Special Updates</div>
                        </div>
                    </div>
                </section>
            </main>

            @include('partials.marketing-footer')
        </div>
    </body>
</html>
