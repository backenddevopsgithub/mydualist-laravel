<x-dashboard.layout :user="$user" title="Edit List - My Dua List">
    <main class="mx-auto max-w-3xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div>
            <a href="{{ $duaList->isArchived() ? route('dashboard', ['tab' => 'archived']) : route('dashboard') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-700">
                Back to dashboard
            </a>
            <h1 class="dashboard-page-title mt-5">Edit List</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600">Update the key details people see when they visit your share page.</p>
        </div>

        <form method="POST" action="{{ route('dashboard.lists.update', $duaList) }}" class="mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
            @csrf
            @method('PATCH')

            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-bold text-stone-900">List Title</label>
                    <input id="title" name="title" value="{{ old('title', $duaList->title) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                    @error('title')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl bg-stone-50 px-4 py-3 text-sm text-stone-600 ring-1 ring-stone-200">
                    <span class="font-bold text-stone-900">Occasion:</span> {{ $duaList->occasionLabel() }}
                    <p class="mt-1 text-xs text-stone-500">Occasion cannot be changed because your share link is based on it.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="block text-sm font-bold text-stone-900">Start Date</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', optional($duaList->start_date)->toDateString()) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @error('start_date')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-bold text-stone-900">End Date</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date', optional($duaList->end_date)->toDateString()) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @error('end_date')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ $duaList->isArchived() ? route('dashboard.archived') : route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 px-5 py-3 text-sm font-bold text-stone-700 transition hover:bg-stone-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Save Changes</button>
            </div>
        </form>
    </main>
</x-dashboard.layout>
