@impersonating
    <div class="fi-impersonation-banner border-b border-warning-400 bg-warning-100 px-4 py-3 text-warning-950 dark:border-warning-600 dark:bg-warning-950 dark:text-warning-100">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-medium">
                Impersonating <span class="font-semibold">{{ auth()->user()?->email }}</span>.
            </p>

            <a
                href="{{ route('impersonate.leave') }}"
                class="inline-flex items-center rounded-lg bg-warning-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-warning-700"
            >
                Stop impersonating
            </a>
        </div>
    </div>
@endImpersonating
