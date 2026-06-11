@php
    $logoUrl = config('mydualist.brand.logo_url');
    $logoSrc = str_starts_with($logoUrl, 'http') ? $logoUrl : asset($logoUrl);
@endphp

<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center']) }} aria-label="My Dua List home">
    <img
        src="{{ $logoSrc }}"
        alt="My Dua List"
        class="h-11 w-auto"
    >
</a>
