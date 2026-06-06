<x-dashboard.layout :user="$user" title="My Submissions - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">My Submissions</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Dua requests you submit to other lists will appear here with status and list references.</p>

        <section class="mt-8 space-y-4">
            @forelse ($submissions as $submission)
                <article class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_18px_60px_rgba(15,23,42,0.06)]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.14em] text-emerald-700">{{ $submission->duaList->title }}</p>
                            <p class="mt-3 text-base leading-7 text-stone-700">{{ $submission->content }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-800">{{ Illuminate\Support\Str::headline($submission->status) }}</span>
                    </div>
                    <p class="mt-4 text-xs font-semibold text-stone-500">Submitted {{ $submission->created_at->diffForHumans() }}</p>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-emerald-950/15 bg-white p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-900">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="mt-5 text-2xl font-extrabold">No submissions yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">When you submit dua requests to other lists, they will appear here.</p>
                </div>
            @endforelse

            @if ($submissions->hasPages())
                <div class="mt-8">
                    {{ $submissions->links() }}
                </div>
            @endif
        </section>
    </main>
</x-dashboard.layout>
