@extends('mail.layout')

@section('content')
    <h1>We received your support request</h1>

    <p>Salam {{ $firstName }},</p>

    <p>Thank you for contacting {{ config('mydualist.name') }}. We have received your support request and our team will review it shortly.</p>

    <p>If you have any additional details to share, simply reply to this email.</p>
@endsection
