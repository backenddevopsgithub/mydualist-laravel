<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->getMetrics() as $metric)
            <x-filament::section>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-950 dark:text-white">{{ $metric['value'] }}</p>
                @if (! empty($metric['description']))
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $metric['description'] }}</p>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
