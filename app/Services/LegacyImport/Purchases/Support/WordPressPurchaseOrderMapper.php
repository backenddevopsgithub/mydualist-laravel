<?php

namespace App\Services\LegacyImport\Purchases\Support;

use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;

class WordPressPurchaseOrderMapper
{
    /**
     * @var list<string>
     */
    public const IMPORTABLE_STATUSES = ['wc-completed', 'wc-processing'];

    /**
     * @var list<int>
     */
    public const SUPPORTED_PRODUCTS = [728, 730, 731, 914, 3211];

    public static function map(
        int $orderId,
        ?int $productId,
        int $customerId,
        int $listId,
        float $total,
        string $currency,
        mixed $createdAt,
    ): ?WordPressOrderRecord {
        if ($productId === null || ! in_array($productId, self::SUPPORTED_PRODUCTS, true)) {
            return null;
        }

        return new WordPressOrderRecord(
            wpOrderId: $orderId,
            productExternalId: $productId,
            customerWpLegacyId: $customerId > 0 ? $customerId : null,
            listWpPostId: $listId > 0 ? $listId : null,
            amountMinor: (int) round($total * 100),
            currency: strtolower($currency !== '' ? $currency : (string) config('billing.currency', 'gbp')),
            status: 'succeeded',
            createdAt: WordPressValueMapper::parseDateTime($createdAt),
        );
    }
}
