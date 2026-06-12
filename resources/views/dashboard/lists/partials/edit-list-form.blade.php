<form method="POST" action="{{ route('dashboard.lists.update', $duaList) }}" class="p-4 sm:p-5">
    @csrf
    @method('PATCH')
    <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-extrabold text-stone-950">Edit List</h2>
        <button type="button" x-on:click="editListOpen = false" class="rounded-full p-2 text-stone-950 transition hover:bg-stone-100" aria-label="Close edit list">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <div class="mt-4 space-y-3">
        <x-ui.input name="title" label="List name" :value="old('title', $duaList->title)" required />

        <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm font-semibold text-stone-600 ring-1 ring-stone-100">
            Occasion: <span class="font-extrabold text-stone-950">{{ $duaList->occasionLabel() }}</span>
        </div>

        <x-ui.input name="start_date" label="Start date" type="date" :value="old('start_date', optional($duaList->start_date)->toDateString())" required />
        <x-ui.input name="end_date" label="End date" type="date" :value="old('end_date', optional($duaList->end_date)->toDateString())" required />
    </div>

    <button type="submit" class="mt-5 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Update list</button>
</form>
