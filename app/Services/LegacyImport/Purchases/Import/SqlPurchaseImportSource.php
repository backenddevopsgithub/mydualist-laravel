<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\SqlDumpReader;

class SqlPurchaseImportSource implements PurchaseImportSource
{
    /**
     * @var list<string>
     */
    private array $completedStatuses = ['wc-completed', 'wc-processing'];

    /**
     * @var list<int>
     */
    private array $supportedProducts = [728, 730, 731, 914, 3211];

    private SqlDumpReader $reader;

    public function __construct(string $path, string $tablePrefix = 'wp_')
    {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function records(): iterable
    {
        foreach ($this->reader->postsById() as $orderId => $post) {
            if (($post['post_type'] ?? '') !== 'shop_order') {
                continue;
            }

            if (! in_array($post['post_status'] ?? '', $this->completedStatuses, true)) {
                continue;
            }

            $meta = $this->reader->postmetaByPostId()[$orderId] ?? [];
            $productId = $this->resolveProductId($orderId);

            if ($productId === null || ! in_array($productId, $this->supportedProducts, true)) {
                continue;
            }

            $customerId = (int) ($meta['_customer_user'] ?? 0);
            $listId = (int) ($meta['_list_id'] ?? 0);
            $total = (float) ($meta['_order_total'] ?? 0);

            yield $orderId => new WordPressOrderRecord(
                wpOrderId: $orderId,
                productExternalId: $productId,
                customerWpLegacyId: $customerId > 0 ? $customerId : null,
                listWpPostId: $listId > 0 ? $listId : null,
                amountMinor: (int) round($total * 100),
                currency: strtolower((string) ($meta['_order_currency'] ?? config('billing.currency', 'gbp'))),
                status: 'succeeded',
                createdAt: WordPressValueMapper::parseDateTime($post['post_date'] ?? null),
            );
        }
    }

    private function resolveProductId(int $orderId): ?int
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
