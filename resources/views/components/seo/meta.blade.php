@props([
    'seo',
])

@php
    /** @var \App\Support\Seo\SeoPresenter $seo */
@endphp

<title>{{ $seo->title }} - My Dua List</title>

@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

@if ($seo->noindex)
    <meta name="robots" content="noindex, nofollow">
@endif

<link rel="canonical" href="{{ $seo->canonicalUrl }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seo->ogTitle }}">
@if ($seo->ogDescription)
    <meta property="og:description" content="{{ $seo->ogDescription }}">
@endif
<meta property="og:url" content="{{ $seo->canonicalUrl }}">
@if ($seo->ogImageUrl)
    <meta property="og:image" content="{{ $seo->ogImageUrl }}">
@endif
<meta name="twitter:card" content="{{ $seo->ogImageUrl ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->ogTitle }}">
@if ($seo->ogDescription)
    <meta name="twitter:description" content="{{ $seo->ogDescription }}">
@endif
@if ($seo->ogImageUrl)
    <meta name="twitter:image" content="{{ $seo->ogImageUrl }}">
@endif
