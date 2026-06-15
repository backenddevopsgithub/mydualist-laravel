@php
    $footerLabels = [
        'help-and-support' => 'Support Center',
        'privacy-policy' => 'Privacy Policy',
        'terms-and-conditions' => 'Terms & Conditions',
    ];

    $footerOrder = [
        'help-and-support',
        'privacy-policy',
        'terms-and-conditions',
    ];

    $orderedPages = isset($cmsFooterPages)
        ? collect($footerOrder)
            ->map(fn (string $slug) => $cmsFooterPages->firstWhere('slug', $slug))
            ->filter()
        : collect();
@endphp

@foreach ($orderedPages as $cmsPage)
    <a href="{{ $cmsPage->publicUrl() }}">
        {{ $footerLabels[$cmsPage->slug] ?? $cmsPage->title }}
    </a>
@endforeach
