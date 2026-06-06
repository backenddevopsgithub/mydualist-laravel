<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'flex items-center gap-3']) }} aria-label="My Dua List home">
    <span class="flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-900/10 bg-white text-emerald-800 shadow-sm">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3.5c3.2 2 5.25 4.8 5.25 8.35A5.25 5.25 0 0 1 12 17.1a5.25 5.25 0 0 1-5.25-5.25C6.75 8.3 8.8 5.5 12 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <path d="M8 20h8M12 17v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
    </span>
    <span class="text-sm font-bold tracking-tight text-stone-950">My Dua List</span>
</a>
