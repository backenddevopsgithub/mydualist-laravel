@extends('mail.layout')

@section('content')
    <h1>Your Dua List is Live! Add an Image for a Personal Touch</h1>

    <p>Salam {{ $listAuthor }},</p>

    <p>Your list, {{ $listName }}, is now live, and it's ready to collect duas! But wait—let's make it even more special.</p>

    <p>Why not add a personal image to your list? A meaningful photo can inspire your submitters and create a deeper connection with every dua they send.</p>

    <p><strong>It's simple, quick, and adds that extra touch to make your list stand out.</strong></p>

    <p><a href="{{ $addImageUrl }}">Add Your Image Now</a></p>

    <p><strong>We're excited to be part of your journey.</strong></p>

    <h1 style="font-size: 22px;">Ready to Experience the Power of Dua?</h1>

    <p style="text-align: center;">
        <a href="{{ $createListUrl }}" class="button button-secondary">Create a New Dua List</a>
    </p>
@endsection
