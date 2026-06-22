<?php

namespace App\Services;

use App\Services\LegacyImport\Validation\LegacyDateBackfillService;
use Illuminate\Support\Facades\File;

class MigrationStatusService extends Service
{
    /**
     * @return list<array{command: string, label: string, phase: string}>
     */
    public static function importSequence(): array
    {
        return [
            ['phase' => '2A', 'command' => 'migrate:users', 'label' => 'Users'],
            ['phase' => '2A', 'command' => 'migrate:suggestions', 'label' => 'Suggestions'],
            ['phase' => '2A', 'command' => 'migrate:lists', 'label' => 'Lists'],
            ['phase' => '2B', 'command' => 'migrate:purchases', 'label' => 'Purchases'],
            ['phase' => '2B', 'command' => 'migrate:submissions', 'label' => 'Submissions'],
            ['phase' => '2B', 'command' => 'migrate:community-duas', 'label' => 'Community duas'],
            ['phase' => 'Validation', 'command' => 'migrate:validate', 'label' => 'Validation'],
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     totals: array<string, int>,
     *     live_totals: array<string, int>,
     *     failures: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     mismatches: list<array<string, mixed>>,
     *     import_sequence: list<array{command: string, label: string, phase: string}>,
     *     generated_at: string|null,
     *     report_path: string|null,
     *     report_exists: bool
     * }
     */
    public function status(): array
    {
        $reportPath = (string) config('mydualist.legacy.import.validate_report_path');
        $liveTotals = LegacyDateBackfillService::liveEntityTotals();
        $importSequence = self::importSequence();

        if (File::exists($reportPath)) {
            $cached = json_decode(File::get($reportPath), true);

            if (is_array($cached) && isset($cached['validation'])) {
                $validation = $cached['validation'];

                return [
                    'passed' => (bool) ($validation['passed'] ?? false),
                    'totals' => $validation['totals'] ?? [],
                    'live_totals' => $liveTotals,
                    'failures' => $validation['failures'] ?? [],
                    'warnings' => $validation['warnings'] ?? [],
                    'mismatches' => $validation['mismatches'] ?? [],
                    'import_sequence' => $importSequence,
                    'generated_at' => is_string($cached['generated_at'] ?? null) ? $cached['generated_at'] : null,
                    'report_path' => $reportPath,
                    'report_exists' => true,
                ];
            }
        }

        return [
            'passed' => false,
            'totals' => [],
            'live_totals' => $liveTotals,
            'failures' => [],
            'warnings' => $this->suggestionsImportHint($liveTotals),
            'mismatches' => [],
            'import_sequence' => $importSequence,
            'generated_at' => null,
            'report_path' => $reportPath,
            'report_exists' => false,
        ];
    }

    /**
     * @param  array<string, int>  $liveTotals
     * @return list<array<string, mixed>>
     */
    private function suggestionsImportHint(array $liveTotals): array
    {
        $users = $liveTotals['users'] ?? 0;
        $suggestions = $liveTotals['suggestions'] ?? 0;

        if ($users > 0 && $suggestions === 0) {
            return [[
                'type' => 'suggestions_not_imported',
                'message' => 'Users exist but no suggestions were imported. Run migrate:suggestions before migrate:lists.',
            ]];
        }

        return [];
    }
}
