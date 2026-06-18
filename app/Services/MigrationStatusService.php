<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class MigrationStatusService extends Service
{
    /**
     * @return array{passed: bool, totals: array<string, int>, failures: list<array<string, mixed>>, warnings: list<array<string, mixed>>, report_path: string|null, report_exists: bool}
     */
    public function status(): array
    {
        $reportPath = (string) config('mydualist.legacy.import.validate_report_path');

        if (File::exists($reportPath)) {
            $cached = json_decode(File::get($reportPath), true);

            if (is_array($cached) && isset($cached['validation'])) {
                $validation = $cached['validation'];

                return [
                    'passed' => (bool) ($validation['passed'] ?? false),
                    'totals' => $validation['totals'] ?? [],
                    'failures' => $validation['failures'] ?? [],
                    'warnings' => $validation['warnings'] ?? [],
                    'report_path' => $reportPath,
                    'report_exists' => true,
                ];
            }
        }

        return [
            'passed' => false,
            'totals' => [],
            'failures' => [],
            'warnings' => [],
            'report_path' => $reportPath,
            'report_exists' => false,
        ];
    }
}
