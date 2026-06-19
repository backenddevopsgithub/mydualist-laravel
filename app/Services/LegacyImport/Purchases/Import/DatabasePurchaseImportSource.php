<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\Support\WordPressHposDetector;
use App\Services\LegacyImport\Purchases\Support\WordPressPurchaseOrderMapper;
use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;

class DatabasePurchaseImportSource implements PurchaseImportSource
{
    public function records(): iterable
    {
        $connection = WordPressConnection::connection();

        if (WordPressHposDetector::usesHpos($connection)) {
            yield from $this->hposRecords($connection);

            return;
        }

        yield from $this->legacyRecords($connection);
    }

    /**
     * @return iterable<int, WordPressOrderRecord>
     */
    private function legacyRecords(Connection $connection): iterable
    {
        $orders = $connection->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', WordPressPurchaseOrderMapper::IMPORTABLE_STATUSES)
            ->orderBy('ID')
            ->get(['ID', 'post_date']);

        foreach ($orders as $order) {
            $orderId = (int) $order->ID;
            $meta = $this->postMeta($connection, $orderId);
            $productId = $this->resolveLegacyProductId($connection, $orderId);
            $record = WordPressPurchaseOrderMapper::map(
                orderId: $orderId,
                productId: $productId,
                customerId: (int) ($meta['_customer_user'] ?? 0),
                listId: (int) ($meta['_list_id'] ?? 0),
                total: (float) ($meta['_order_total'] ?? 0),
                currency: (string) ($meta['_order_currency'] ?? config('billing.currency', 'gbp')),
                createdAt: $order->post_date,
            );

            if ($record !== null) {
                yield $orderId => $record;
            }
        }
    }

    /**
     * @return iterable<int, WordPressOrderRecord>
     */
    private function hposRecords(Connection $connection): iterable
    {
        $orders = $connection->table('wc_orders')
            ->whereIn('status', WordPressPurchaseOrderMapper::IMPORTABLE_STATUSES)
            ->where('type', 'shop_order')
            ->orderBy('id')
            ->get([
                'id',
                'status',
                'currency',
                'total_amount',
                'customer_id',
                'date_created_gmt',
                'date_created',
            ]);

        $listIdsByOrder = [];

        if ($connection->getSchemaBuilder()->hasTable('wc_orders_meta')) {
            $listIdsByOrder = $connection->table('wc_orders_meta')
                ->where('meta_key', '_list_id')
                ->pluck('meta_value', 'order_id')
                ->map(fn ($value): int => (int) $value)
                ->all();
        }

        $productIdsByOrder = $this->productIdsFromLookup($connection);

        foreach ($orders as $order) {
            $orderId = (int) $order->id;
            $productId = $productIdsByOrder[$orderId] ?? $this->resolveLegacyProductId($connection, $orderId);
            $createdAt = $order->date_created_gmt ?? $order->date_created ?? null;

            $record = WordPressPurchaseOrderMapper::map(
                orderId: $orderId,
                productId: $productId,
                customerId: (int) ($order->customer_id ?? 0),
                listId: (int) ($listIdsByOrder[$orderId] ?? 0),
                total: (float) ($order->total_amount ?? 0),
                currency: (string) ($order->currency ?? config('billing.currency', 'gbp')),
                createdAt: $createdAt,
            );

            if ($record !== null) {
                yield $orderId => $record;
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function productIdsFromLookup(Connection $connection): array
    {
        if (! $connection->getSchemaBuilder()->hasTable('wc_order_product_lookup')) {
            return [];
        }

        $productIdsByOrder = [];

        $rows = $connection->table('wc_order_product_lookup')
            ->orderBy('order_item_id')
            ->get(['order_id', 'product_id']);

        foreach ($rows as $row) {
            $orderId = (int) $row->order_id;
            $productId = (int) ($row->product_id ?? 0);

            if ($productId > 0 && ! isset($productIdsByOrder[$orderId])) {
                $productIdsByOrder[$orderId] = $productId;
            }
        }

        return $productIdsByOrder;
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(Connection $connection, int $postId): array
    {
        return $connection->table('postmeta')
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private function resolveLegacyProductId(Connection $connection, int $orderId): ?int
    {
        $item = $connection->table('woocommerce_order_items')
            ->where('order_id', $orderId)
            ->where('order_item_type', 'line_item')
            ->orderBy('order_item_id')
            ->first(['order_item_id']);

        if ($item === null) {
            return null;
        }

        $productId = $connection->table('woocommerce_order_itemmeta')
            ->where('order_item_id', $item->order_item_id)
            ->where('meta_key', '_product_id')
            ->value('meta_value');

        return $productId !== null ? (int) $productId : null;
    }
}
