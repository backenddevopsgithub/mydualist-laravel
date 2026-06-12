@props([
    'back' => null,
    'submit' => 'Next',
    'disabled' => false,
])

<div class="mt-10 flex items-center justify-between gap-4">
    @if ($back)
        <x-ui.button variant="neutral" size="sm" :href="route('onboarding.show', $back)">
            <span aria-hidden="true">←</span>
            Back
        </x-ui.button>
    @else
        <span></span>
    @endif

    <x-ui.button
        type="submit"
        variant="primary"
        size="md"
        disabled
        x-bind:disabled="typeof canSubmit !== 'undefined' ? ! canSubmit : {{ $disabled ? 'true' : 'false' }}"
        {{ $attributes }}
    >
        {{ $submit }}
        <span aria-hidden="true">→</span>
    </x-ui.button>
</div>
