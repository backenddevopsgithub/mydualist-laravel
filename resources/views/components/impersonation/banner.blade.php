@impersonating
    <div class="sticky top-0 z-50 border-b border-amber-300 bg-amber-100 px-4 py-3 text-amber-950 shadow-sm">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold">
                You are impersonating
                <span class="font-extrabold">{{ auth()->user()?->email }}</span>.
                Sensitive account actions are disabled.
            </p>

            <a
                href="{{ route('impersonate.leave') }}"
                class="inline-flex items-center rounded-lg bg-amber-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-amber-50 transition hover:bg-amber-950"
            >
                Stop impersonating
            </a>
        </div>
    </div>
@endImpersonating
