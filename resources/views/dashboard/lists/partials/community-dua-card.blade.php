<article class="overflow-hidden rounded-[1.65rem] border border-emerald-200 bg-emerald-50/40 shadow-sm sm:rounded-[2rem]" x-data="{ reportOpen: false, reason: '' }">
    <div class="border-b border-emerald-100 bg-white px-5 py-4 sm:px-7">
        <h2 class="text-lg font-extrabold text-emerald-950">Community Duas</h2>
        <p class="mt-1 text-xs text-stone-600">Duas from the general Muslim community, shown after you complete personal requests.</p>
    </div>

    <div class="p-5 sm:p-7">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-xl font-black text-stone-950">{{ $communityDua->displayName() }}</h3>
                @if (in_array($communityDua->gender, ['male', 'female'], true))
                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-stone-500">{{ ucfirst($communityDua->gender) }}</p>
                @endif
            </div>
            <span class="rounded-full bg-emerald-900 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-white">
                {{ $communityDua->type->value === 'paid' ? 'Paid' : 'Free' }} Community Dua
            </span>
        </div>

        <p class="mt-5 text-[1.02rem] leading-8 text-stone-800 sm:text-[1.2rem]">{{ $communityDua->content }}</p>

        <p class="mt-4 text-xs font-bold uppercase tracking-wide text-stone-400">
            Progress: {{ $communityDua->completion_count }}/{{ $communityDua->required_completions }} completions
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-emerald-100 bg-emerald-950 px-5 py-4 text-white sm:px-7">
        <button type="button" x-on:click="reportOpen = true" class="rounded-full bg-white/10 px-3 py-1 text-xs font-extrabold">Report</button>

        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('dashboard.community-duas.skip', [$duaList, $communityDua]) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-2xl border border-white/20 px-4 py-2 text-sm font-extrabold">Show next</button>
            </form>

            <form method="POST" action="{{ route('dashboard.community-duas.complete', [$duaList, $communityDua]) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-2xl bg-lime-400 px-4 py-2 text-sm font-extrabold text-emerald-950">Mark complete</button>
            </form>
        </div>
    </div>

    <template x-teleport="body">
        <div x-cloak x-show="reportOpen" class="fixed inset-0 z-50 flex items-end bg-stone-950/40 p-4 backdrop-blur-sm sm:items-center sm:justify-center">
            <form method="POST" action="{{ route('dashboard.community-duas.report', [$duaList, $communityDua]) }}" class="w-full max-w-md rounded-[2rem] bg-white p-6 shadow-2xl">
                @csrf
                @method('PATCH')
                <h3 class="text-xl font-extrabold text-stone-950">Report community dua</h3>
                <div class="mt-4 space-y-2">
                    @foreach (['spam' => 'Spam', 'offensive' => 'Offensive content', 'duplicate' => 'Duplicate', 'irrelevant' => 'Irrelevant', 'other' => 'Other'] as $value => $label)
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input type="radio" name="report_reason" value="{{ $value }}" x-model="reason" required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4" x-show="reason === 'other'">
                    <textarea name="report_note" rows="4" class="w-full rounded-2xl border border-stone-200 px-4 py-3 text-sm"></textarea>
                </div>
                <div class="mt-5 flex gap-3">
                    <button type="button" x-on:click="reportOpen = false" class="flex-1 rounded-2xl border border-stone-200 px-4 py-3 text-sm font-extrabold">Cancel</button>
                    <button type="submit" class="flex-1 rounded-2xl bg-red-600 px-4 py-3 text-sm font-extrabold text-white">Submit report</button>
                </div>
            </form>
        </div>
    </template>
</article>
