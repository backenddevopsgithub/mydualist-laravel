<x-dashboard.layout :user="$user" title="Upgrade Plan - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">Upgrade Plan</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Unlock unlimited lists and every dua request your loved ones send.</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                Current Plan: {{ $currentPlan }}
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        @error('billing')
            <div class="mt-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-bold text-red-700 ring-1 ring-red-100">
                {{ $message }}
            </div>
        @enderror

        <section class="mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
            <h2 class="text-xl font-extrabold">Usage</h2>
            @if ($hasPremium)
                <p class="mt-3 text-sm font-semibold text-stone-600">Premium is active. You have unlimited lists and all submissions are unlocked.</p>
            @else
                <div class="mt-5 h-3 rounded-full bg-stone-100">
                    <div class="h-3 rounded-full bg-emerald-700" style="width: {{ min(100, round(($activeListsCount / max((int) $listLimit, 1)) * 100)) }}%"></div>
                </div>
                <p class="mt-3 text-sm font-semibold text-stone-600">
                    {{ $activeListsCount }} of {{ $listLimit }} active lists used.
                    {{ $remainingListSlots }} list {{ $remainingListSlots === 1 ? 'slot' : 'slots' }} remaining.
                </p>
                <p class="mt-2 text-sm text-stone-500">Free lists show the first {{ $visibleSubmissionLimit }} submissions. Additional duas are locked until you upgrade.</p>
            @endif
        </section>

        <section class="mt-8 grid gap-5 lg:grid-cols-2">
            @foreach ([
                ['Free', '$0', 'For getting started', ['Create up to 2 active lists', 'First 25 submissions visible per list', 'Public sharing and owner workspace'], false],
                ['Premium', $premiumCurrency.' '.$premiumPrice, 'One-time unlock', ['Unlimited active lists', 'All submissions visible', 'Premium-ready dashboard access'], true],
            ] as [$name, $price, $description, $features, $featured])
                <article @class([
                    'rounded-[2rem] border bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)]',
                    'border-emerald-800 ring-4 ring-emerald-100' => $featured,
                    'border-emerald-950/10' => ! $featured,
                ])>
                    <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-emerald-700">{{ $name }}</p>
                    <p class="mt-4 text-4xl font-extrabold text-stone-950">{{ $price }}</p>
                    <p class="mt-2 text-sm text-stone-600">{{ $description }}</p>
                    <ul class="mt-6 space-y-3 text-sm font-semibold text-stone-700">
                        @foreach ($features as $feature)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-emerald-600"></span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($featured)
                        @if ($hasPremium)
                            <div class="mt-7 w-full rounded-2xl bg-emerald-50 px-5 py-3 text-center text-sm font-extrabold text-emerald-900 ring-1 ring-emerald-900/10">
                                Premium Unlocked
                            </div>
                        @else
                            <form method="POST" action="{{ route('billing.checkout') }}" class="mt-7">
                                @csrf
                                <button type="submit" class="w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">
                                    Upgrade with Stripe
                                </button>
                            </form>
                        @endif
                    @else
                        <div class="mt-7 w-full rounded-2xl bg-stone-100 px-5 py-3 text-center text-sm font-extrabold text-stone-600">
                            Current Free Access
                        </div>
                    @endif
                </article>
            @endforeach
        </section>
    </main>
</x-dashboard.layout>
