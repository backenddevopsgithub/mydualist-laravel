@extends('mail.layout')

@section('content')
    <h1>{{ $listAuthor }}, Your Dua List is Ready – Explore Our Key Features!</h1>

    <p>Salam {{ $listAuthor }},</p>

    <p>Thank you for trusting {{ config('mydualist.name') }} to manage your dua completion.</p>

    <p>Here is a quick overview of our key features:</p>

    <ul>
        <li><strong>Dua limits:</strong> Set how many duas each person can submit.</li>
        <li><strong>Display order:</strong> Choose how duas appear on your list.</li>
        <li><strong>Email notifications:</strong> Get real-time alerts or a daily summary.</li>
        <li><strong>Complete, hide, or flag duas:</strong> Full control over every submission.</li>
    </ul>

    <p style="text-align: center;">
        <a href="{{ $dashboardUrl }}" class="button">Access Your Dashboard</a>
    </p>

    <p>If you need any assistance, we are always here to help.</p>

    <p>May Allah accept all your duas and grant you ease in your journey.</p>

    <p><strong>JazakAllah Khair,</strong><br>The My Dua List Team</p>
@endsection
