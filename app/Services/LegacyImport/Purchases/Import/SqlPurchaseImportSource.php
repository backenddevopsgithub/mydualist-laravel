<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\Support\WordPressHposDetector;
use App\Services\LegacyImport\Purchases\Support\WordPressPurchaseOrderMapper;
use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Support\WordPress\SqlDumpReader;

class SqlPurchaseImportSource implements PurchaseImportSource
{
    private SqlDumpReader $reader;

    public function __construct(string $path, string $tablePrefix = 'wp_')
    {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function records(): iterable
    {
        if (WordPressHposDetector::dumpUsesHpos($this->reader)) {
            yield from $this->hposRecords();

            return;
        }

        yield from $this->legacyRecords();
    }

    /**
     * @return iterable<int, WordPressOrderRecord>
     */
    private function legacyRecords(): iterable
    {
        foreach ($this->reader->postsById() as $orderId => $post) {
            if (($post['post_type'] ?? '') !== 'shop_order') {
                continue;
            }

            if (! in_array($post['post_status'] ?? '', WordPressPurchaseOrderMapper::IMPORTABLE_STATUSES, true)) {
                continue;
            }

            $meta = $this->reader->postmetaByPostId()[$orderId] ?? [];
            $productId = $this->resolveLegacyProductId($orderId);

            $record = WordPressPurchaseOrderMapper::map(
                orderId: $orderId,
                productId: $productId,
                customerId: (int) ($meta['_customer_user'] ?? 0),
                listId: (int) ($meta['_list_id'] ?? 0),
                total: (float) ($meta['_order_total'] ?? 0),
                currency: (string) ($meta['_order_currency'] ?? config('billing.currency', 'gbp')),
                createdAt: $post['post_date'] ?? null,
            );

            if ($record !== null) {
                yield $orderId => $record;
            }
        }
    }

    /**
     * @return iterable<int, WordPressOrderRecord>
     */
    private function hposRecords(): iterable
    {
        foreach ($this->reader->wcOrdersById() as $orderId => $order) {
            if (($order['type'] ?? 'shop_order') !== 'shop_order') {
                continue;
            }

            if (! in_array($order['status'] ?? '', WordPressPurchaseOrderMapper::IMPORTABLE_STATUSES, true)) {
                continue;
            }

            $meta = $this->reader->wcOrderMetaByOrderId()[$orderId] ?? [];
            $productId = $this->reader->wcProductIdForOrder($orderId) ?? $this->resolveLegacyProductId($orderId);
            $createdAt = $order['date_created_gmt'] ?? $order['date_created'] ?? null;

            $record = WordPressPurchaseOrderMapper::map(
                orderId: $orderId,
                productId: $productId,
                customerId: (int) ($order['customer_id'] ?? $meta['_customer_user'] ?? 0),
                listId: (int) ($meta['_list_id'] ?? 0),
                total: (float) ($order['total_amount'] ?? $meta['_order_total'] ?? 0),
                currency: (string) ($order['currency'] ?? $meta['_order_currency'] ?? config('billing.currency', 'gbp')),
                createdAt: $createdAt,
            );

            if ($record !== null) {
                yield $orderId => $record;
            }
        }
    }

    private function resolveLegacyProductId(int $orderId): ?int
    {
        foreach ($this->reader->orderItemsForOrder($orderId) as $item) {
            if ($item['order_item_type'] !== 'line_item') {
                continue;
            }

            $productId = (int) ($this->reader->orderItemMeta($item['order_item_id'])['_product_id'] ?? 0);

            if ($productId > 0) {
                return $productId;
            }
        }

        return null;
    }
}
