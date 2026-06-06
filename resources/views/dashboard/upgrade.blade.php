<x-dashboard.layout :user="$user" title="Upgrade Plan - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">Upgrade Plan</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">A subscription-ready foundation for plans and limits. Payments will be connected in a later phase.</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                Current Plan: {{ $currentPlan }}
            </div>
        </div>

        <section class="mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
            <h2 class="text-xl font-extrabold">Usage</h2>
            <div class="mt-5 h-3 rounded-full bg-stone-100">
                <div class="h-3 rounded-full bg-emerald-700" style="width: {{ min(100, round(($activeListsCount / max($listLimit, 1)) * 100)) }}%"></div>
            </div>
            <p class="mt-3 text-sm font-semibold text-stone-600">{{ $activeListsCount }} of {{ $listLimit }} active lists used on the Free plan.</p>
        </section>

        <section class="mt-8 grid gap-5 lg:grid-cols-3">
            @foreach ([
                ['Free', '$0', 'For getting started', ['Create up to 3 active lists', 'Share public list links', 'Basic dashboard access'], false],
                ['Premium', '$4.99', 'For active dua list creators', ['Unlimited active lists', 'More submission capacity', 'Priority product features'], true],
                ['Family', '$9.99', 'For households and groups', ['Shared family list management', 'Higher limits', 'Family-focused tools'], false],
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
                    <button type="button" class="mt-7 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800" disabled>
                        Payments Coming Soon
                    </button>
                </article>
            @endforeach
        </section>
    </main>
</x-dashboard.layout>
