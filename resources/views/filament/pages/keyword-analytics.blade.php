<x-filament-panels::page>
    <div wire:init="loadMetrics">
        @if ($metricsLoaded)
            <div class="mb-6 grid gap-4 md:grid-cols-3">
                @foreach ($this->getMetricCards() as $card)
                    <x-filament::section>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</p>
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

        @if ($chartsLoaded)
            @php
                $chart = $this->getTopKeywordsChartData();
                $cloud = $this->getWordCloudData();
            @endphp
            <div class="mb-6 grid gap-4 lg:grid-cols-2">
                <x-filament::section heading="Top 10 Keywords">
                    <canvas id="keyword-bar-chart" height="220"></canvas>
                </x-filament::section>
                <x-filament::section heading="Word Cloud">
                    <div class="flex min-h-56 flex-wrap items-center justify-center gap-3 p-4 text-center">
                        @forelse ($cloud as $word)
                            <span style="font-size: {{ $word['size'] }}px;" class="font-medium text-primary-600 dark:text-primary-400">
                                {{ $word['keyword'] }}
                            </span>
                        @empty
                            <p class="text-sm text-gray-500">No keywords to display.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            @push('scripts')
                <script src="{{ asset('js/filament/widgets/components/chart.js') }}"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const el = document.getElementById('keyword-bar-chart');
                        if (! el) return;
                        new Chart(el, {
                            type: 'bar',
                            data: {
                                labels: @json($chart['labels']),
                                datasets: [{
                                    label: 'Occurrences',
                                    data: @json($chart['data']),
                                    backgroundColor: '#059669',
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                            },
                        });
                    });
                </script>
            @endpush
        @else
            <div class="mb-6 grid gap-4 lg:grid-cols-2">
                @foreach (range(1, 2) as $chartPlaceholder)
                    <x-filament::section>
                        <div class="flex h-56 animate-pulse items-center justify-center rounded bg-gray-100 dark:bg-gray-800">
                            <span class="text-sm text-gray-500">Loading charts…</span>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif

        {{ $this->table }}
    </div>
</x-filament-panels::page>
