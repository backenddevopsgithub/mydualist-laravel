<?php

namespace App\Console\Commands;

use App\Domains\Billing\Services\BillingHealthService;
use Illuminate\Console\Command;

class BillingHealthCommand extends Command
{
    protected $signature = 'billing:health
        {--alert : Emit alerts to logs and optional Slack webhook}';

    protected $description = 'Report billing health metrics and optional alerts';

    public function handle(BillingHealthService $health): int
    {
        $snapshot = $health->snapshot();
        $alerts = $health->alerts();

        $this->info('Billing health snapshot');
        $this->table(
            ['Metric', 'Value'],
            collect($snapshot)->map(fn (mixed $value, string $key): array => [
                $key,
                is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            ])->values()->all(),
        );

        if ($alerts === []) {
            $this->info('No billing alerts.');

            return self::SUCCESS;
        }

        $this->warn('Billing alerts:');
        foreach ($alerts as $alert) {
            $this->line("- {$alert}");
        }

        if ($this->option('alert')) {
            $health->notifyIfNeeded();
            $this->comment('Alert notifications dispatched.');
        }

        return self::FAILURE;
    }
}
