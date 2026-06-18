@php
    $payload = json_decode($record->payload ?? '{}', true);
    $exception = (string) ($record->exception ?? '');
@endphp

<div class="space-y-4 text-sm">
    <div>
        <p class="font-medium text-gray-950 dark:text-white">Job</p>
        <p class="text-gray-600 dark:text-gray-300">{{ $payload['displayName'] ?? 'Unknown job' }}</p>
    </div>
    <div>
        <p class="font-medium text-gray-950 dark:text-white">Queue</p>
        <p class="text-gray-600 dark:text-gray-300">{{ $record->queue }} ({{ $record->connection }})</p>
    </div>
    <div>
        <p class="font-medium text-gray-950 dark:text-white">Failed At</p>
        <p class="text-gray-600 dark:text-gray-300">{{ $record->failed_at }}</p>
    </div>
    <div>
        <p class="mb-2 font-medium text-gray-950 dark:text-white">Exception</p>
        <pre class="max-h-96 overflow-auto rounded-lg bg-gray-100 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ \App\Support\ExceptionSanitizer::forDisplay($exception) }}</pre>
    </div>
</div>
