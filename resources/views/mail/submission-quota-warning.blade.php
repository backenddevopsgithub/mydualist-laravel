@extends('mail.layout')

@section('content')
    <h1>Your Dua List is Almost Full – Time to Upgrade for More Requests!</h1>

    <p>Salam {{ $listAuthor }},</p>

    <p>MashaAllah, your dua list <strong>{{ $listName }}</strong> is approaching its free-plan limit. So many people are counting on you to remember them in your prayers.</p>

    <p>You have only <strong>5 spots remaining</strong> before additional requests will be greyed out on the free plan. Upgrade to ensure everyone who wants to submit a dua can do so.</p>

    <p style="text-align: center;">
        <a href="{{ $upgradeUrl }}" class="button">Upgrade Your Plan Now</a>
    </p>

    <p>We pray Allah accepts all your duas and grants ease in your journey.</p>

    <p><strong>JazakAllah Khair,</strong><br>The My Dua List Team</p>
@endsection
