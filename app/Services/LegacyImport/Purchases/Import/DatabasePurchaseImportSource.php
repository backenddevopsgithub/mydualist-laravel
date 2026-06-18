<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;

class DatabasePurchaseImportSource implements PurchaseImportSource
{
    /**
     * @var list<string>
     */
    private array $completedStatuses = ['wc-completed', 'wc-processing'];

    /**
     * @var list<int>
     */
    private array $supportedProducts = [728, 730, 731, 914, 3211];

    public function records(): iterable
    {
        $connection = WordPressConnection::connection();
        $prefix = WordPressConnection::prefix();

        $orders = $connection->table("{$prefix}posts")
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', $this->completedStatuses)
            ->orderBy('ID')
            ->get(['ID', 'post_date']);

        foreach ($orders as $order) {
            $orderId = (int) $order->ID;
            $meta = $this->postMeta($connection, $prefix, $orderId);
            $record = $this->mapOrder($orderId, $meta, $connection, $prefix, $order->post_date);

            if ($record !== null) {
                yield $orderId => $record;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(Connection $connection, string $prefix, int $postId): array
    {
        return $connection->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function mapOrder(
        int $orderId,
        array $meta,
        Connection $connection,
        string $prefix,
        mixed $postDate,
    ): ?WordPressOrderRecord {
        $productId = $this->resolveProductId($connection, $prefix, $orderId);

        if ($productId === null || ! in_array($productId, $this->supportedProducts, true)) {
            return null;
        }

        $customerId = (int) ($meta['_customer_user'] ?? 0);
        $listId = (int) ($meta['_list_id'] ?? 0);
        $total = (float) ($meta['_order_total'] ?? 0);

        return new WordPressOrderRecord(
            wpOrderId: $orderId,
            productExternalId: $productId,
            customerWpLegacyId: $customerId > 0 ? $customerId : null,
            listWpPostId: $listId > 0 ? $listId : null,
            amountMinor: (int) round($total * 100),
            currency: strtolower((string) ($meta['_order_currency'] ?? config('billing.currency', 'gbp'))),
            status: 'succeeded',
            createdAt: WordPressValueMapper::parseDateTime($postDate),
        );
    }

    private function resolveProductId(
        Connection $connection,
        string $prefix,
        int $orderId,
    ): ?int {
        $item = $connection->table("{$prefix}woocommerce_order_items")
            ->where('order_id', $orderId)
            ->where('order_item_type', 'line_item')
            ->orderBy('order_item_id')
            ->first(['order_item_id']);

        if ($item === null) {
            return null;
        }

        $productId = $connection->table("{$prefix}woocommerce_order_itemmeta")
            ->where('order_item_id', $item->order_item_id)
            ->where('meta_key', '_product_id')
            ->value('meta_value');

        return $productId !== null ? (int) $productId : null;
    }
}
