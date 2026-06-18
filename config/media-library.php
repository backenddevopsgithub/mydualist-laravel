<?php

return [

    'disk_name' => env('MEDIA_DISK', 'media'),

    'conversions_disk_name' => env('MEDIA_CONVERSIONS_DISK', 'media'),

    'max_file_size' => 1024 * 1024 * 50,

    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),

    'queue_connection_name' => env('MEDIA_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),

    'queue_name' => env('MEDIA_QUEUE', ''),

];
