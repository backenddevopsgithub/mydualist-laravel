<?php

namespace Database\Seeders;

use App\Enums\BillingProductScope;
use App\Models\BillingProduct;
use Illuminate\Database\Seeder;

class BillingProductSeeder extends Seeder
{
    public function run(): void
    {
        $currency = (string) config('billing.currency', 'gbp');

        foreach (config('billing.products', []) as $code => $product) {
            BillingProduct::query()->updateOrCreate(
                ['code' => $code],
                [
                    'external_product_id' => (int) $product['external_id'],
                    'name' => (string) $product['name'],
                    'scope' => BillingProductScope::from((string) $product['scope']),
                    'stackable' => (bool) ($product['stackable'] ?? false),
                    'requires_authentication' => (bool) ($product['requires_authentication'] ?? true),
                    'amount_minor' => (int) $product['amount_minor'],
                    'currency' => $currency,
                    'active' => true,
                    'metadata' => [
                        'seeded_from' => 'config/billing.php',
                    ],
                ],
            );
        }
    }
}
