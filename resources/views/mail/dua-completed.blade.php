@extends('mail.layout')

@section('content')
    <h1>{{ $duaAuthor }} Just Completed Your Dua Request</h1>

    <p>Salam {{ $requestedBy }},</p>

    <p>We are delighted to let you know that {{ $duaAuthor }} has just completed your dua request. Alhamdulillah!</p>

    <p>{{ $duaAuthor }} has taken your prayers to heart and successfully included them in {{ $possessivePronoun }} supplications during {{ $possessivePronoun }} {{ $occasionLabel }} journey.</p>

    <p>Be sure to thank them for remembering you in their duas.</p>

  @if ($listImageUrl)
        <p style="text-align: center;">
            <img src="{{ $listImageUrl }}" alt="{{ $listTitle }}" style="max-width: 100%; border-radius: 10px; border: 1px solid #d9d9d9;">
        </p>
    @endif

    <div class="panel">
        <p style="margin-bottom: 8px;"><strong>Dua requested:</strong></p>
        <p style="margin-bottom: 0;">{{ $duaMessage }}</p>
    </div>

    <p class="muted" style="text-align: center;">Help us facilitate more duas by making a donation or leaving a review.</p>

    <p style="text-align: center;">
        <a href="https://donorbox.org/pilgrim-2?default_interval=m" class="button">Make a donation</a>
        <a href="https://www.trustpilot.com/evaluate/mydualist.com" class="button button-secondary">Leave a review</a>
    </p>
@endsection
