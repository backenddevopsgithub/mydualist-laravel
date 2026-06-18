<x-filament-panels::page>
    @php($stats = $this->getHealthStats())

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <p class="text-sm text-gray-500">Queue Connection</p>
            <p class="text-xl font-semibold">{{ $stats['connection'] }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Pending Jobs</p>
            <p class="text-xl font-semibold">{{ number_format($stats['pending']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Failed Jobs</p>
            <p class="text-xl font-semibold text-danger-600">{{ number_format($stats['failed']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-sm text-gray-500">Scheduler Heartbeat</p>
            <p class="text-xl font-semibold">{{ $stats['scheduler_status'] }}</p>
            <p class="text-xs text-gray-500">Last run: {{ $stats['scheduler_last_run'] }}</p>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
