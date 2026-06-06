@props([
    'back' => null,
    'submit' => 'Continue',
])

<div class="mt-10 flex items-center justify-between gap-4">
    @if ($back)
        <a href="{{ route('onboarding.show', $back) }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-900 hover:text-emerald-700">
            <span aria-hidden="true">←</span>
            Back
        </a>
    @else
        <span></span>
    @endif

    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
        {{ $submit }}
        <span aria-hidden="true">→</span>
    </button>
</div>
