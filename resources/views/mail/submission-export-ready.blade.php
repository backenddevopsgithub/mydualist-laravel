@extends('mail.layout')

@section('content')
    <h1>Your CSV export is ready</h1>

    <p>Salam {{ $recipientName }},</p>

    <p>Your export for <strong>{{ $listTitle }}</strong> is ready to download.</p>

    <p>It contains {{ number_format($rowCount) }} submission{{ $rowCount === 1 ? '' : 's' }}.</p>

    <p style="text-align: center;">
        <a href="{{ $downloadUrl }}" class="button">Download CSV</a>
    </p>

    <p>This link expires in {{ $expiresInDays }} day{{ $expiresInDays === 1 ? '' : 's' }}.</p>

    <p><strong>JazakAllah Khair,</strong><br>The My Dua List Team</p>
@endsection
