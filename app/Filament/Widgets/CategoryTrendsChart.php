<?php

namespace App\Filament\Widgets;

use App\Models\DuaList;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class CategoryTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Top List Categories';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $rows = DuaList::query()
            ->selectRaw('occasion, COUNT(*) as aggregate')
            ->groupBy('occasion')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->pluck('aggregate', 'occasion');

        return [
            'datasets' => [
                [
                    'label' => 'Lists',
                    'data' => $rows->values()->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => ['#064e3b', '#047857', '#059669', '#10b981', '#34d399', '#a7f3d0', '#f59e0b', '#fcd34d'],
                ],
            ],
            'labels' => $rows->keys()->map(fn (string $key): string => Str::headline(str_replace('-', ' ', $key)))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
