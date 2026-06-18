@extends('mail.layout')

@section('content')
    <h1>{{ $duaAuthor }} has completed your salawat request</h1>

    <p>Salam {{ $requestedBy }},</p>

    <p>We're delighted to let you know that {{ $duaAuthor }} given Salawat on your behalf. Alhamdulillah!</p>

    <p>{{ $duaAuthor }} has made sure to include you as part of {{ $possessivePronoun }} salawat list, conveying your salam to the prophet, peace and blessings be upon him.</p>

    <p><strong>Be sure to thank them profusely by sending a message to let them know you're grateful.</strong></p>

    @if ($listImageUrl)
        <p style="text-align: center;">
            <img src="{{ $listImageUrl }}" alt="{{ $listTitle }}" style="max-width: 100%; border-radius: 10px; border: 1px solid #d9d9d9;">
        </p>
    @endif

    <p class="muted" style="text-align: center;">Abu Huraira reported: The Prophet, peace and blessings be upon him, said, "Whoever does not thank people has not thanked Allah."</p>

    <h1 style="font-size: 22px;">Ready to Experience the Power of Dua?</h1>

    <p style="text-align: center;">
        <a href="{{ $createListUrl }}" class="button button-secondary">Create a New Dua List</a>
    </p>
@endsection
