@extends('mail.layout')

@section('content')
    <h1>Welcome to My Dua List</h1>

    <p>Salam,</p>

    <p>Welcome to {{ config('mydualist.name') }}. Your account is verified and you can now manage your dua lists from your dashboard.</p>

    <p>Collect heartfelt duas from family and friends, track completion, and stay organised throughout your journey.</p>

    <p style="text-align: center;">
        <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
    </p>

    <p>May Allah accept all your duas and grant you ease.</p>

    <p><strong>JazakAllah Khair,</strong><br>The My Dua List Team</p>
@endsection
