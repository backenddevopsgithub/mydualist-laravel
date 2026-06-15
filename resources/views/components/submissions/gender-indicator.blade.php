@props([
    'gender',
])

@php
    $normalized = App\Support\SubmissionGenders::normalize($gender);
    $label = App\Support\SubmissionGenders::label($normalized);
@endphp

@if ($normalized && $label)
    <span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1.5 text-sm font-bold text-stone-600 sm:text-base']) }} aria-label="{{ $label }} submitter">
        @if ($normalized === App\Support\SubmissionGenders::MALE)
            <svg class="h-4 w-4 text-emerald-800 dark:text-emerald-300 sm:h-[1.125rem] sm:w-[1.125rem]" viewBox="0 0 24 24" fill="none" role="img" aria-hidden="true">
                <circle cx="12" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.8" />
                <path d="M6.5 20.5c.8-3.2 2.8-5 5.5-5s4.7 1.8 5.5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        @else
            <svg class="h-4 w-4 text-emerald-800 dark:text-emerald-300 sm:h-[1.125rem] sm:w-[1.125rem]" viewBox="0 0 24 24" fill="none" role="img" aria-hidden="true">
                <circle cx="12" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.8" />
                <path d="M7 20.5c.9-2.8 2.7-4.5 5-4.5s4.1 1.7 5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path d="M9.5 11.5 7 9M14.5 11.5 17 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        @endif
        <span aria-hidden="true">{{ $label }}</span>
    </span>
@endif
