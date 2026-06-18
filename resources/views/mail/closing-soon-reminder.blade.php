@extends('mail.layout')

@section('content')
    <h1>Your List titled '{{ $listName }}' Is Closing Soon. Last Chance To Extend.</h1>

    <p>Salam {{ $listAuthor }},</p>

    <p>This is a reminder that your dua list <a href="{{ $listUrl }}">{{ $listName }}</a> is set to close in {{ $daysRemaining }} days on date {{ $listClosingDate }}. You will not be able to receive new dua submissions after this date.</p>

    <p>If you'd still like to keep receiving dua requests beyond this date, you need to extend your list before it closes. Once closed, you will not be able to re-open the list.</p>

    <p style="text-align: center;">
        <a href="{{ $editListUrl }}" class="button button-secondary">Extend Your List Now</a>
    </p>

    <p>If you don't wish to extend, ignore this email.</p>

    <p><strong>JazakAllah Khair,</strong><br>The {{ config('mydualist.name') }} Team</p>
@endsection
