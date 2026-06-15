@extends('mail.layout')

@section('content')
    <h1>You Just Received A Dua Request</h1>

    <p>Salam {{ $ownerName }},</p>

    <p><strong>{{ $requestedBy }}</strong> just submitted a dua request to your list <strong>{{ $listTitle }}</strong>.</p>

    <p style="text-align: center;">
        <a href="{{ $viewSubmissionsUrl }}" class="button">View Request</a>
    </p>

    <p>May Allah accept every dua made for you.</p>
@endsection
