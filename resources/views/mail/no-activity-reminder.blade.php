@extends('mail.layout')

@section('content')
    <h1>Your Dua List is Inactive – Don't Miss Out on More Duas!</h1>

    <p>Salam {{ $listAuthor }},</p>

    <p>We noticed that your dua list for {{ $listName }} has been a bit quiet recently, with no submissions. If you're still waiting for duas from friends and family, it might be a good time to remind them by sharing your list again.</p>

    <p>Here is your personalized shareable link:<br><a href="{{ $listUrl }}">Shareable Link</a></p>

    <p>Remember, by sharing your list, you're giving others the chance to be part of your journey and your duas. Every dua counts, and Allah rewards those who make dua for others.</p>

    <p>If you have any questions or need help, we're here for you!</p>

    <p><strong>JazakAllah Khair,</strong><br>The {{ config('mydualist.name') }} Team</p>

    <h1 style="font-size: 22px;">Ready to Experience the Power of Dua?</h1>

    <p style="text-align: center;">
        <a href="{{ $createListUrl }}" class="button button-secondary">Create a New Dua List</a>
    </p>
@endsection
