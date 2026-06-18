<x-filament-panels::page>
    <div wire:init="loadMetrics">
        @if ($metricsLoaded)
            <div class="mb-6 grid gap-4 md:grid-cols-3">
                @foreach ($this->getMetricCards() as $card)
                    <x-filament::section>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                @if (! empty($card['url']))
                                    <a href="{{ $card['url'] }}" class="hover:underline">{{ $card['value'] }}</a>
                                @else
                                    {{ $card['value'] }}
                                @endif
                            </p>
                            @if (! empty($card['description']))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @else
            <div class="mb-6 grid gap-4 md:grid-cols-3">
                @foreach (range(1, 3) as $placeholder)
                    <x-filament::section>
                        <div class="animate-pulse space-y-2">
                            <div class="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-8 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif

        {{ $this->table }}
    </div>
</x-filament-panels::page>
