<?php

namespace App\Support\WordPress;

class SqlDumpReader
{
    /**
     * @var array<int, array<string, string|null>>
     */
    private array $postsById = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $postmetaByPostId = [];

    /**
     * @var array<int, array<string, string|null>>
     */
    private array $usersById = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $usermetaByUserId = [];

    /**
     * @var array<int, array{name: string, slug: string}>
     */
    private array $termsById = [];

    /**
     * @var array<int, int>
     */
    private array $termIdByTaxonomyId = [];

    /**
     * @var array<string, array<int, int>>
     */
    private array $taxonomyIdsByName = [];

    /**
     * @var array<int, list<int>>
     */
    private array $taxonomyIdsByPostId = [];

    /**
     * @var array<int, list<array{order_item_id: int, order_item_type: string}>>
     */
    private array $orderItemsByOrderId = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $orderItemMetaByItemId = [];

    /**
     * @var array<int, array<string, string|null>>
     */
    private array $wcOrdersById = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $wcOrderMetaByOrderId = [];

    /**
     * @var array<int, int>
     */
    private array $wcProductIdByOrderId = [];

    /**
     * @var list<string>
     */
    private array $wcOrdersColumns = [];

    public function __construct(
        private readonly string $path,
        private readonly string $prefix = 'wp_',
    ) {
        if (! is_readable($this->path)) {
            throw new \RuntimeException("SQL import file is not readable: {$this->path}");
        }

        $sql = file_get_contents($this->path);

        if ($sql === false) {
            throw new \RuntimeException("Unable to read SQL import file: {$this->path}");
        }

        $this->postsById = $this->indexPosts(SqlInsertParser::parseTableRows($sql, $this->prefix.'posts'));
        $this->postmetaByPostId = $this->indexPostmeta(SqlInsertParser::parseTableRows($sql, $this->prefix.'postmeta'));
        $this->usersById = $this->indexUsers(SqlInsertParser::parseTableRows($sql, $this->prefix.'users'));
        $this->usermetaByUserId = $this->indexUsermeta(SqlInsertParser::parseTableRows($sql, $this->prefix.'usermeta'));
        $this->indexTaxonomy(
            SqlInsertParser::parseTableRows($sql, $this->prefix.'terms'),
            SqlInsertParser::parseTableRows($sql, $this->prefix.'term_taxonomy'),
            SqlInsertParser::parseTableRows($sql, $this->prefix.'term_relationships'),
        );
        $this->indexWooCommerce(
            SqlInsertParser::parseTableRows($sql, $this->prefix.'woocommerce_order_items'),
            SqlInsertParser::parseTableRows($sql, $this->prefix.'woocommerce_order_itemmeta'),
        );
        $this->indexHposOrders(
            SqlInsertParser::parseTableRows($sql, $this->prefix.'wc_orders'),
            SqlInsertParser::parseTableRows($sql, $this->prefix.'wc_orders_meta'),
            SqlInsertParser::parseTableRows($sql, $this->prefix.'wc_order_product_lookup'),
        );
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function postsById(): array
    {
        return $this->postsById;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function postmetaByPostId(): array
    {
        return $this->postmetaByPostId;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function usersById(): array
    {
        return $this->usersById;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function usermetaByUserId(): array
    {
        return $this->usermetaByUserId;
    }

    /**
     * @return array{name: string, slug: string}|null
     */
    public function primaryTaxonomyTermForPost(int $postId, string $taxonomy): ?array
    {
        foreach ($this->taxonomyIdsByPostId[$postId] ?? [] as $taxonomyId) {
            $termId = $this->termIdByTaxonomyId[$taxonomyId] ?? null;

            if ($termId === null || ! isset($this->termsById[$termId])) {
                continue;
            }

            if (! in_array($taxonomyId, $this->taxonomyIdsByName[$taxonomy] ?? [], true)) {
                continue;
            }

            return $this->termsById[$termId];
        }

        return null;
    }

    /**
     * @return list<array{order_item_id: int, order_item_type: string}>
     */
    public function orderItemsForOrder(int $orderId): array
    {
        return $this->orderItemsByOrderId[$orderId] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function orderItemMeta(int $orderItemId): array
    {
        return $this->orderItemMetaByItemId[$orderItemId] ?? [];
    }

    public function hasHposOrders(): bool
    {
        return $this->wcOrdersById !== [];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function wcOrdersById(): array
    {
        return $this->wcOrdersById;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function wcOrderMetaByOrderId(): array
    {
        return $this->wcOrderMetaByOrderId;
    }

    public function wcProductIdForOrder(int $orderId): ?int
    {
        return $this->wcProductIdByOrderId[$orderId] ?? null;
    }

    /**
     * @return list<string>
     */
    public function wcOrdersColumns(): array
    {
        return $this->wcOrdersColumns;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string|null>>
     */
    private function indexPosts(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['ID'])) {
                $indexed[(int) $row['ID']] = $row;

                continue;
            }

            if (isset($row[0])) {
                $indexed[(int) $row[0]] = [
                    'ID' => (string) $row[0],
                    'post_author' => $row[1] ?? null,
                    'post_date' => $row[2] ?? null,
                    'post_date_gmt' => $row[3] ?? null,
                    'post_content' => $row[4] ?? null,
                    'post_title' => $row[5] ?? null,
                    'post_excerpt' => $row[6] ?? null,
                    'post_status' => $row[7] ?? null,
                    'post_name' => $row[11] ?? null,
                    'post_modified' => $row[14] ?? null,
                    'guid' => $row[18] ?? null,
                    'post_type' => $row[19] ?? null,
                    'post_mime_type' => $row[20] ?? null,
                ];
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string>>
     */
    private function indexPostmeta(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['post_id'], $row['meta_key'])) {
                $indexed[(int) $row['post_id']][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');

                continue;
            }

            if (isset($row[1], $row[2])) {
                $indexed[(int) $row[1]][(string) $row[2]] = (string) ($row[3] ?? '');
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string|null>>
     */
    private function indexUsers(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['ID'])) {
                $indexed[(int) $row['ID']] = $row;

                continue;
            }

            if (isset($row[0])) {
                $indexed[(int) $row[0]] = [
                    'ID' => (string) $row[0],
                    'user_login' => $row[1] ?? null,
                    'user_pass' => $row[2] ?? null,
                    'user_email' => $row[4] ?? null,
                    'user_registered' => $row[6] ?? null,
                    'display_name' => $row[9] ?? null,
                ];
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string>>
     */
    private function indexUsermeta(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['user_id'], $row['meta_key'])) {
                $indexed[(int) $row['user_id']][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');

                continue;
            }

            if (isset($row[1], $row[2])) {
                $indexed[(int) $row[1]][(string) $row[2]] = (string) ($row[3] ?? '');
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $termRows
     * @param  list<array<string, string|null>|list<string|null>>  $taxonomyRows
     * @param  list<array<string, string|null>|list<string|null>>  $relationshipRows
     */
    private function indexTaxonomy(array $termRows, array $taxonomyRows, array $relationshipRows): void
    {
        foreach ($termRows as $row) {
            if (isset($row['term_id'])) {
                $this->termsById[(int) $row['term_id']] = [
                    'name' => (string) ($row['name'] ?? ''),
                    'slug' => (string) ($row['slug'] ?? ''),
                ];

                continue;
            }

            if (isset($row[0])) {
                $this->termsById[(int) $row[0]] = [
                    'name' => (string) ($row[1] ?? ''),
                    'slug' => (string) ($row[2] ?? ''),
                ];
            }
        }

        foreach ($taxonomyRows as $row) {
            if (isset($row['term_taxonomy_id'], $row['taxonomy'], $row['term_id'])) {
                $taxonomyId = (int) $row['term_taxonomy_id'];
                $taxonomy = (string) $row['taxonomy'];
                $this->termIdByTaxonomyId[$taxonomyId] = (int) $row['term_id'];
                $this->taxonomyIdsByName[$taxonomy][] = $taxonomyId;

                continue;
            }

            if (isset($row[0], $row[2], $row[1])) {
                $taxonomyId = (int) $row[0];
                $taxonomy = (string) $row[2];
                $this->termIdByTaxonomyId[$taxonomyId] = (int) $row[1];
                $this->taxonomyIdsByName[$taxonomy][] = $taxonomyId;
            }
        }

        foreach ($relationshipRows as $row) {
            $objectId = isset($row['object_id']) ? (int) $row['object_id'] : (isset($row[1]) ? (int) $row[1] : 0);
            $taxonomyId = isset($row['term_taxonomy_id']) ? (int) $row['term_taxonomy_id'] : (isset($row[2]) ? (int) $row[2] : 0);

            if ($objectId <= 0 || $taxonomyId <= 0) {
                continue;
            }

            $this->taxonomyIdsByPostId[$objectId][] = $taxonomyId;
        }
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $itemRows
     * @param  list<array<string, string|null>|list<string|null>>  $itemMetaRows
     */
    private function indexWooCommerce(array $itemRows, array $itemMetaRows): void
    {
        foreach ($itemRows as $row) {
            if (isset($row['order_id'], $row['order_item_id'])) {
                $orderId = (int) $row['order_id'];
                $this->orderItemsByOrderId[$orderId][] = [
                    'order_item_id' => (int) $row['order_item_id'],
                    'order_item_type' => (string) ($row['order_item_type'] ?? 'line_item'),
                ];

                continue;
            }

            if (isset($row[0], $row[1])) {
                $orderId = (int) $row[1];
                $this->orderItemsByOrderId[$orderId][] = [
                    'order_item_id' => (int) $row[0],
                    'order_item_type' => (string) ($row[2] ?? 'line_item'),
                ];
            }
        }

        foreach ($itemMetaRows as $row) {
            if (isset($row['order_item_id'], $row['meta_key'])) {
                $this->orderItemMetaByItemId[(int) $row['order_item_id']][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');

                continue;
            }

            if (isset($row[1], $row[2])) {
                $this->orderItemMetaByItemId[(int) $row[1]][(string) $row[2]] = (string) ($row[3] ?? '');
            }
        }
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $orderRows
     * @param  list<array<string, string|null>|list<string|null>>  $metaRows
     * @param  list<array<string, string|null>|list<string|null>>  $lookupRows
     */
    private function indexHposOrders(array $orderRows, array $metaRows, array $lookupRows): void
    {
        foreach ($orderRows as $row) {
            if (isset($row['id'])) {
                $this->wcOrdersById[(int) $row['id']] = $row;
                $this->wcOrdersColumns = array_values(array_unique(array_merge(
                    $this->wcOrdersColumns,
                    array_keys($row),
                )));

                continue;
            }

            if (isset($row[0])) {
                $positionalColumns = [
                    'id',
                    'status',
                    'currency',
                    'type',
                    'tax_amount',
                    'total_amount',
                    'customer_id',
                    'billing_email',
                    'date_created_gmt',
                    'date_updated_gmt',
                ];

                $this->wcOrdersColumns = array_values(array_unique(array_merge(
                    $this->wcOrdersColumns,
                    $positionalColumns,
                )));

                $this->wcOrdersById[(int) $row[0]] = [
                    'id' => (string) $row[0],
                    'status' => $row[1] ?? null,
                    'currency' => $row[2] ?? null,
                    'type' => $row[3] ?? null,
                    'tax_amount' => $row[4] ?? null,
                    'total_amount' => $row[5] ?? null,
                    'customer_id' => $row[6] ?? null,
                    'billing_email' => $row[7] ?? null,
                    'date_created_gmt' => $row[8] ?? null,
                    'date_updated_gmt' => $row[9] ?? null,
                ];
            }
        }

        foreach ($metaRows as $row) {
            if (isset($row['order_id'], $row['meta_key'])) {
                $this->wcOrderMetaByOrderId[(int) $row['order_id']][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');

                continue;
            }

            if (isset($row[1], $row[2])) {
                $this->wcOrderMetaByOrderId[(int) $row[1]][(string) $row[2]] = (string) ($row[3] ?? '');
            }
        }

        foreach ($lookupRows as $row) {
            $orderId = isset($row['order_id']) ? (int) $row['order_id'] : (isset($row[1]) ? (int) $row[1] : 0);
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : (isset($row[2]) ? (int) $row[2] : 0);

            if ($orderId <= 0 || $productId <= 0 || isset($this->wcProductIdByOrderId[$orderId])) {
                continue;
            }

            $this->wcProductIdByOrderId[$orderId] = $productId;
        }
    }
}
