<x-filament-panels::page>
    @php($status = $this->getStatus())

    <div class="mb-6">
        <x-filament::section>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Validation Status</p>
                    <p class="text-2xl font-semibold {{ $status['passed'] ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $status['passed'] ? 'Passed' : 'Failed' }}
                    </p>
                </div>
                <x-filament::badge :color="$status['report_exists'] ? 'info' : 'warning'">
                    {{ $status['report_exists'] ? 'Cached report available' : 'Live validation snapshot' }}
                </x-filament::badge>
            </div>
            @if ($status['report_path'])
                <p class="mt-2 text-xs text-gray-500">Report path: {{ $status['report_path'] }}</p>
            @endif
        </x-filament::section>
    </div>

    @if (! empty($status['live_totals']))
        <x-filament::section heading="Live Database Counts" class="mb-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($status['live_totals'] as $metric => $count)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $metric) }}</p>
                        <p class="text-lg font-semibold">{{ number_format($count) }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if (! empty($status['totals']))
        <x-filament::section heading="Cached Validation Totals" class="mb-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($status['totals'] as $metric => $count)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $metric) }}</p>
                        <p class="text-lg font-semibold">{{ number_format($count) }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if (! empty($status['failures']))
        <x-filament::section heading="Failures" class="mb-6">
            <ul class="space-y-2 text-sm text-danger-600">
                @foreach ($status['failures'] as $failure)
                    <li class="rounded bg-danger-50 p-2 dark:bg-danger-500/10">{{ json_encode($failure) }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

    @if (! empty($status['warnings']))
        <x-filament::section heading="Warnings">
            <ul class="space-y-2 text-sm text-warning-600">
                @foreach ($status['warnings'] as $warning)
                    <li class="rounded bg-warning-50 p-2 dark:bg-warning-500/10">{{ json_encode($warning) }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
