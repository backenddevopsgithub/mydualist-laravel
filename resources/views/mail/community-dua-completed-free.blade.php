@extends('mail.layout')

@section('content')
    <p>Salam {{ $duaAuthor }},</p>

    <p>The Dua you submitted to the general community has just been completed by {{ $completedBy }}.</p>

    <p><strong>May your days be filled with blessings and positivity!</strong></p>

    <h2>Ready to Experience the Power of Dua?</h2>

    <p style="text-align: center;">
        <a href="{{ route('onboarding.start') }}" class="button">Create Your Dua List</a>
    </p>
@endsection
