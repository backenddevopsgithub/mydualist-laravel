<x-filament-panels::page>
    <div wire:init="loadMetrics">
        @if ($metricsLoaded)
            <div class="mb-6 grid gap-4 md:grid-cols-3">
                @foreach ($this->getMetricCards() as $card)
                    <x-filament::section>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</p>
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

        @if ($chartsLoaded)
            @php
                $donut = $this->getDonutChartData();
                $trend = $this->getTrendChartData();
            @endphp
            <div class="mb-6 grid gap-4 lg:grid-cols-2">
                <x-filament::section heading="Top Categories">
                    <canvas id="category-donut-chart" height="220"></canvas>
                </x-filament::section>
                <x-filament::section heading="List Creation Trend">
                    <canvas id="category-trend-chart" height="220"></canvas>
                </x-filament::section>
            </div>

            @push('scripts')
                <script src="{{ asset('js/filament/widgets/components/chart.js') }}"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const donutEl = document.getElementById('category-donut-chart');
                        if (donutEl) {
                            new Chart(donutEl, {
                                type: 'doughnut',
                                data: {
                                    labels: @json($donut['labels']),
                                    datasets: [{
                                        data: @json($donut['data']),
                                        backgroundColor: ['#064e3b', '#047857', '#059669', '#10b981', '#34d399', '#a7f3d0', '#f59e0b', '#fcd34d'],
                                    }],
                                },
                                options: { responsive: true, maintainAspectRatio: false },
                            });
                        }

                        const trendEl = document.getElementById('category-trend-chart');
                        if (trendEl) {
                            new Chart(trendEl, {
                                type: 'line',
                                data: {
                                    labels: @json($trend['labels']),
                                    datasets: [{
                                        label: 'Lists Created',
                                        data: @json($trend['data']),
                                        borderColor: '#059669',
                                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                        fill: true,
                                        tension: 0.3,
                                    }],
                                },
                                options: { responsive: true, maintainAspectRatio: false },
                            });
                        }
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
