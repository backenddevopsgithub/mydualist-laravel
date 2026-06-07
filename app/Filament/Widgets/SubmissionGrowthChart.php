<?php

namespace App\Filament\Widgets;

use App\Models\DuaSubmission;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SubmissionGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Submission Growth';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $start = now()->subDays(13)->startOfDay();
        $rows = DuaSubmission::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('aggregate', 'day');

        $labels = [];
        $data = [];

        for ($date = $start->copy(); $date->lte(now()); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = Carbon::parse($key)->format('M j');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Duas',
                    'data' => $data,
                    'borderColor' => '#047857',
                    'backgroundColor' => 'rgba(4, 120, 87, 0.12)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
