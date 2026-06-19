<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\Support\WordPressHposDetector;
use App\Services\LegacyImport\Purchases\Support\WordPressHposOrderTimestamps;
use App\Services\LegacyImport\Purchases\Support\WordPressOrderBillingEmailResolver;
use App\Services\LegacyImport\Purchases\Support\WordPressPurchaseOrderMapper;
use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

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
                billingEmail: WordPressOrderBillingEmailResolver::fromLegacyMeta($meta),
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
        $availableColumns = Schema::connection($connection->getName())->getColumnListing('wc_orders');
        $timestampColumns = WordPressHposOrderTimestamps::forConnection($connection);

        $orders = $connection->table('wc_orders')
            ->whereIn('status', WordPressPurchaseOrderMapper::IMPORTABLE_STATUSES)
            ->where('type', 'shop_order')
            ->orderBy('id')
            ->get($this->hposSelectColumns($availableColumns));

        $listIdsByOrder = [];

        if ($connection->getSchemaBuilder()->hasTable('wc_orders_meta')) {
            $listIdsByOrder = $connection->table('wc_orders_meta')
                ->where('meta_key', '_list_id')
                ->pluck('meta_value', 'order_id')
                ->map(fn ($value): int => (int) $value)
                ->all();
        }

        $productIdsByOrder = $this->productIdsFromLookup($connection);
        $billingEmailsByOrder = $this->billingEmailsByOrder($connection, $availableColumns);
        $billingMetaEmailsByOrder = $this->billingMetaEmailsByOrder($connection);

        foreach ($orders as $order) {
            $orderId = (int) $order->id;
            $productId = $productIdsByOrder[$orderId] ?? $this->resolveLegacyProductId($connection, $orderId);
            $createdAt = WordPressHposOrderTimestamps::createdAt($order, $timestampColumns);
            $billingEmail = WordPressOrderBillingEmailResolver::fromHposOrder(
                (array) $order,
                [],
                $billingEmailsByOrder[$orderId] ?? $billingMetaEmailsByOrder[$orderId] ?? null,
            );

            $record = WordPressPurchaseOrderMapper::map(
                orderId: $orderId,
                productId: $productId,
                customerId: (int) ($order->customer_id ?? 0),
                listId: (int) ($listIdsByOrder[$orderId] ?? 0),
                total: (float) ($order->total_amount ?? 0),
                currency: (string) ($order->currency ?? config('billing.currency', 'gbp')),
                createdAt: $createdAt,
                billingEmail: $billingEmail,
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

    /**
     * @param  list<string>  $availableColumns
     * @return list<string>
     */
    private function hposSelectColumns(array $availableColumns): array
    {
        $select = WordPressHposOrderTimestamps::selectColumns($availableColumns);

        if (in_array('billing_email', $availableColumns, true) && ! in_array('billing_email', $select, true)) {
            $select[] = 'billing_email';
        }

        return $select;
    }

    /**
     * @param  list<string>  $availableColumns
     * @return array<int, string>
     */
    private function billingEmailsByOrder(Connection $connection, array $availableColumns): array
    {
        if (! $connection->getSchemaBuilder()->hasTable('wc_order_addresses')) {
            return [];
        }

        return $connection->table('wc_order_addresses')
            ->where('address_type', 'billing')
            ->whereNotNull('email')
            ->pluck('email', 'order_id')
            ->map(fn ($email): ?string => WordPressOrderBillingEmailResolver::normalize((string) $email))
            ->filter()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function billingMetaEmailsByOrder(Connection $connection): array
    {
        if (! $connection->getSchemaBuilder()->hasTable('wc_orders_meta')) {
            return [];
        }

        return $connection->table('wc_orders_meta')
            ->where('meta_key', '_billing_email')
            ->pluck('meta_value', 'order_id')
            ->map(fn ($email): ?string => WordPressOrderBillingEmailResolver::normalize((string) $email))
            ->filter()
            ->all();
    }
}
