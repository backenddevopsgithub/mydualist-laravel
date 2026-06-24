@extends('mail.layout')

@section('content')
    <h1>New Support Request Received</h1>

    <p>A new support request was submitted on {{ config('mydualist.name') }}.</p>

    <table cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; margin: 24px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; font-weight: 600; width: 140px;">Name</td>
            <td style="padding: 8px 0;">{{ $ticket->first_name }} {{ $ticket->surname }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: 600;">Email</td>
            <td style="padding: 8px 0;">{{ $ticket->email }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: 600;">Subject</td>
            <td style="padding: 8px 0;">{{ $reasonLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: 600; vertical-align: top;">Message</td>
            <td style="padding: 8px 0; white-space: pre-wrap;">{{ $ticket->comments }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: 600;">Submitted</td>
            <td style="padding: 8px 0;">{{ $submittedAt }}</td>
        </tr>
    </table>
@endsection
