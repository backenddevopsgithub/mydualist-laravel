<?php

namespace App\Services\LegacyImport\Purchases\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

class WordPressHposOrderTimestamps
{
    /**
     * @param  list<string>  $columns
     * @return array{created: string, updated: string}
     */
    public static function columns(array $columns): array
    {
        return [
            'created' => in_array('date_created', $columns, true)
                ? 'date_created'
                : 'date_created_gmt',
            'updated' => in_array('date_updated', $columns, true)
                ? 'date_updated'
                : 'date_updated_gmt',
        ];
    }

    /**
     * @return array{created: string, updated: string}
     */
    public static function forConnection(Connection $connection): array
    {
        return self::columns(
            Schema::connection($connection->getName())->getColumnListing('wc_orders'),
        );
    }

    /**
     * @param  array<string, string|null>|object  $order
     * @param  array{created: string, updated: string}  $timestampColumns
     */
    public static function createdAt(array|object $order, array $timestampColumns): mixed
    {
        $primary = $timestampColumns['created'];
        $fallback = $primary === 'date_created' ? 'date_created_gmt' : 'date_created';

        if (is_array($order)) {
            return $order[$primary] ?? $order[$fallback] ?? null;
        }

        return $order->{$primary} ?? $order->{$fallback} ?? null;
    }

    /**
     * @param  list<string>  $availableColumns
     * @return list<string>
     */
    public static function selectColumns(array $availableColumns): array
    {
        $timestampColumns = self::columns($availableColumns);

        $select = [
            'id',
            'status',
            'currency',
            'total_amount',
            'customer_id',
            $timestampColumns['created'],
        ];

        return array_values(array_filter(
            $select,
            static fn (string $column): bool => in_array($column, $availableColumns, true),
        ));
    }
}
