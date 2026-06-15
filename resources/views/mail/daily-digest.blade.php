@extends('mail.layout')

@section('content')
    <h1>You Just Received A Dua Request</h1>

    <p>Salam {{ $ownerName }},</p>

    <p>
        You received <strong>{{ $submissionCount }}</strong>
        new dua request{{ $submissionCount === 1 ? '' : 's' }} for
        <strong>{{ $listTitle }}</strong>.
    </p>

    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin: 16px 0;">
        <thead>
            <tr>
                <th align="left" style="border-bottom: 2px solid #d9d9d9; padding-bottom: 8px;">Dua submitted by</th>
                <th align="left" style="border-bottom: 2px solid #d9d9d9; padding-bottom: 8px;">Preview</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submitterRows as $row)
                <tr>
                    <td style="border-bottom: 1px solid #eeeeee; vertical-align: top;">{{ $row['name'] }}</td>
                    <td style="border-bottom: 1px solid #eeeeee; vertical-align: top;">{{ $row['preview'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($previewSubmissions->isNotEmpty())
        <div class="panel">
            <p style="margin-bottom: 8px;"><strong>Recent requests:</strong></p>
            @foreach ($previewSubmissions as $submission)
                <p style="margin-bottom: 8px;">
                    <strong>{{ $submission->displayName() }}:</strong>
                    {{ \Illuminate\Support\Str::limit($submission->content, 160) }}
                </p>
            @endforeach
        </div>
    @endif

    <p style="text-align: center;">
        <a href="{{ $viewSubmissionsUrl }}" class="button">Manage Submissions</a>
    </p>

    <p>May Allah accept every dua made for you.</p>
@endsection
