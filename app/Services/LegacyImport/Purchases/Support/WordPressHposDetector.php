<?php

namespace App\Services\LegacyImport\Purchases\Support;

use App\Support\WordPress\SqlDumpReader;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

class WordPressHposDetector
{
    public static function usesHpos(?Connection $connection = null): bool
    {
        $connection ??= WordPressConnection::connection();

        return Schema::connection($connection->getName())->hasTable('wc_orders');
    }

    public static function dumpUsesHpos(SqlDumpReader $reader): bool
    {
        return $reader->hasHposOrders();
    }
}
