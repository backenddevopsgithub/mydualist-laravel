<?php

namespace App\Support\WordPress;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WordPressConnection
{
    public static function connection(): Connection
    {
        if (blank(env('WP_DB_DATABASE'))) {
            throw new RuntimeException('WordPress database is not configured. Set WP_DB_* environment variables.');
        }

        return DB::connection('wordpress');
    }

    /**
     * Table prefix for raw SQL and SQL dump parsing only.
     *
     * The wordpress connection query builder applies the configured prefix
     * automatically — pass unprefixed table names to ->table().
     */
    public static function prefix(): string
    {
        return static::connection()->getTablePrefix();
    }
}
