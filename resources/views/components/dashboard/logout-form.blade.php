<form method="POST" action="{{ route('logout') }}" {{ $attributes }}>
    @csrf
    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-xs font-bold text-stone-800 transition hover:bg-white hover:text-red-700">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-stone-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 12H3m12 0-3-3m3 3-3 3M9 7V5.5A1.5 1.5 0 0 1 10.5 4h8A1.5 1.5 0 0 1 20 5.5v13A1.5 1.5 0 0 1 18.5 20h-8A1.5 1.5 0 0 1 9 18.5V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        {{ $slot->isEmpty() ? 'Log Out' : $slot }}
    </button>
</form>
