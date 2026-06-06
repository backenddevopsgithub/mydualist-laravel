<?php

return [

    'name' => env('MYDUALIST_NAME', 'MyDualist'),

    'api' => [
        'version' => 'v1',
        'prefix' => 'api/v1',
    ],

    'defaults' => [
        'pagination' => [
            'per_page' => 15,
            'max_per_page' => 100,
        ],
    ],

];
