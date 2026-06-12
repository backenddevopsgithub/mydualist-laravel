<x-dashboard.layout :user="$user" title="Upgrade Plan - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="dashboard-page-title">Upgrade Plan</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Unlock more lists and unlimited dua requests.</p>
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

        <section class="mt-8 grid gap-5 lg:grid-cols-3">
            @foreach ([
                [
                    'id' => 'additional_list',
                    'name' => 'One Additional List',
                    'price' => '£7.99',
                    'description' => 'Adds one extra list with unlimited dua requests.',
                    'features' => ['1 additional active list', 'Unlimited dua requests on that list', 'Premium support'],
                ],
                [
                    'id' => 'unlimited_one_list',
                    'name' => 'Unlimited One List',
                    'price' => '£7.99',
                    'description' => 'Unlock unlimited dua requests on one existing list.',
                    'features' => ['Unlimited requests on one list', 'Choose which list to upgrade', 'Premium support'],
                    'hasListSelect' => true,
                ],
                [
                    'id' => 'unlimited_forever',
                    'name' => 'Unlimited Forever',
                    'price' => '£11.99',
                    'description' => 'Unlimited requests and unlimited lists for life.',
                    'features' => ['Unlimited dua requests', 'Unlimited dua lists', 'Ad-free experience', 'Premium support'],
                    'featured' => true,
                ],
            ] as $plan)
                <x-ui.card @class([
                    'ring-4 ring-emerald-100' => $plan['featured'] ?? false,
                    '!border-emerald-800' => $plan['featured'] ?? false,
                ])>
                    @if ($plan['featured'] ?? false)
                        <span class="mb-4 inline-flex rounded-full bg-amber-300 px-3 py-1 text-xs font-extrabold text-emerald-950">Popular</span>
                    @endif
                    <p class="text-lg font-extrabold text-stone-950">{{ $plan['name'] }}</p>
                    <p class="mt-3 text-4xl font-extrabold text-stone-950">{{ $plan['price'] }}</p>
                    <p class="mt-2 text-sm text-stone-600">{{ $plan['description'] }}</p>
                    <ul class="mt-6 space-y-3 text-sm font-semibold text-stone-700">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-600"></span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('billing.checkout') }}" class="mt-7 space-y-4">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan['id'] }}">
                        @if ($plan['hasListSelect'] ?? false)
                            <x-ui.select name="dua_list_id" label="List" required>
                                <option value="">Select a list</option>
                                @foreach ($user->duaLists()->active()->orderBy('title')->get() as $list)
                                    <option value="{{ $list->id }}">{{ $list->title }}</option>
                                @endforeach
                            </x-ui.select>
                        @endif
                        <x-ui.button type="submit" variant="primary" full-width>Upgrade</x-ui.button>
                    </form>
                </x-ui.card>
            @endforeach
        </section>
    </main>
</x-dashboard.layout>
