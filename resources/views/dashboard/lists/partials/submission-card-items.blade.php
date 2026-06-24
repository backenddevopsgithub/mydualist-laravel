@foreach ($submissions as $submission)
    @include('dashboard.lists.partials.submission-card', [
        'submission' => $submission,
        'duaList' => $duaList,
        'submissions' => $submissions,
        'visibleSubmissionLimit' => $visibleSubmissionLimit,
    ])
@endforeach

<template
    data-submissions-scroll-page-meta
    data-next-page-url="{{ $nextSubmissionPageUrl }}"
    data-has-more="{{ $submissions->hasMorePages() ? 'true' : 'false' }}"
></template>
