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

    public static function prefix(): string
    {
        return (string) config('database.connections.wordpress.prefix', 'wp_');
    }
}
