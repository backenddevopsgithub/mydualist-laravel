<?php

namespace App\Services\LegacyImport\Purchases;

use Carbon\Carbon;

readonly class WordPressOrderRecord
{
    public function __construct(
        public int $wpOrderId,
        public int $productExternalId,
        public ?int $customerWpLegacyId,
        public ?int $listWpPostId,
        public int $amountMinor,
        public string $currency,
        public string $status,
        public ?Carbon $createdAt,
    ) {}

    /**
     * @return array{wp_order_id: int, product_external_id: int}
     */
    public function summary(): array
    {
        return [
            'wp_order_id' => $this->wpOrderId,
            'product_external_id' => $this->productExternalId,
        ];
    }
}
