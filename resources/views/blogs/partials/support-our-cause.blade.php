@if ($supportOurCause['enabled'] ?? false)
    <aside class="mt-10 rounded-3xl bg-[#f9fff5] p-8 ring-1 ring-emerald-900/10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            @if ($supportOurCauseImageUrl)
                <img src="{{ $supportOurCauseImageUrl }}" alt="" class="h-24 w-24 shrink-0 rounded-2xl object-cover">
            @endif

            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-extrabold text-stone-950">{{ $supportOurCause['heading'] }}</h2>
                <p class="mt-3 text-base leading-7 text-stone-700">{{ $supportOurCause['description'] }}</p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a
                        href="{{ $supportOurCause['primary_button_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800"
                    >
                        {{ $supportOurCause['primary_button_text'] }}
                    </a>

                    @if (filled($supportOurCause['secondary_button_text'] ?? null) && filled($supportOurCause['secondary_button_url'] ?? null))
                        <a
                            href="{{ $supportOurCause['secondary_button_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center rounded-2xl border border-emerald-900/15 bg-white px-5 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-50"
                        >
                            {{ $supportOurCause['secondary_button_text'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </aside>
@endif
