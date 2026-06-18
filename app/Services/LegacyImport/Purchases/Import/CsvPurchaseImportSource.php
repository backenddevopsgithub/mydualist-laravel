<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use RuntimeException;

class CsvPurchaseImportSource implements PurchaseImportSource
{
    /**
     * @var list<int>
     */
    private array $supportedProducts = [728, 730, 731, 914, 3211];

    public function __construct(
        private readonly string $path,
    ) {}

    public function records(): iterable
    {
        if (! is_readable($this->path)) {
            throw new RuntimeException("CSV import file is not readable: {$this->path}");
        }

        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV import file: {$this->path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return;
            }

            $headers = array_map(fn (string $header): string => strtolower(trim($header)), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                /** @var array<string, string|null> $data */
                $data = array_combine($headers, array_pad($row, count($headers), null));

                if ($data === false) {
                    continue;
                }

                $record = $this->mapRow($data);

                if ($record !== null) {
                    yield $record->wpOrderId => $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapRow(array $data): ?WordPressOrderRecord
    {
        $wpOrderId = (int) ($data['wp_order_id'] ?? $data['id'] ?? 0);
        $productId = (int) ($data['product_external_id'] ?? $data['product_id'] ?? 0);

        if ($wpOrderId <= 0 || ! in_array($productId, $this->supportedProducts, true)) {
            return null;
        }

        $customerId = (int) ($data['customer_wp_legacy_id'] ?? $data['user_id'] ?? 0);
        $listId = (int) ($data['list_wp_post_id'] ?? $data['list_id'] ?? 0);
        $amount = (float) ($data['amount_minor'] ?? ((float) ($data['order_total'] ?? 0) * 100));

        return new WordPressOrderRecord(
            wpOrderId: $wpOrderId,
            productExternalId: $productId,
            customerWpLegacyId: $customerId > 0 ? $customerId : null,
            listWpPostId: $listId > 0 ? $listId : null,
            amountMinor: (int) round($amount),
            currency: strtolower((string) ($data['currency'] ?? config('billing.currency', 'gbp'))),
            status: 'succeeded',
            createdAt: WordPressValueMapper::parseDateTime($data['created_at'] ?? $data['post_date'] ?? null),
        );
    }
}
