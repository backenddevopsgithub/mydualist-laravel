<?php

namespace App\Console\Commands;

use App\Domains\Billing\Services\PurchaseReconciliationService;
use Illuminate\Console\Command;

class ReconcileBillingPurchasesCommand extends Command
{
    protected $signature = 'billing:reconcile-purchases
        {--purchase= : Reconcile a single billing purchase ID}
        {--dry-run : Report mismatches without updating records}';

    protected $description = 'Reconcile billing purchases against Stripe Payment Intent status and fulfill succeeded purchases';

    public function handle(PurchaseReconciliationService $reconciliation): int
    {
        $purchaseId = $this->option('purchase');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no database updates will be made.');
        }

        $result = $reconciliation->reconcile(
            $purchaseId !== null ? (int) $purchaseId : null,
            $dryRun,
        );

        $this->table(
            ['Metric', 'Count'],
            [
                ['Checked', (string) $result['checked']],
                ['Updated', (string) $result['updated']],
                ['Fulfilled', (string) $result['fulfilled']],
                ['Failures', (string) count($result['failures'])],
            ],
        );

        foreach ($result['failures'] as $failure) {
            $this->error($failure);
        }

        return $result['failures'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
