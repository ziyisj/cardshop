<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache/data'),
        ],
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver'         => 'database',
            'table'          => 'cache',
            'connection'     => null,
            'lock_connection'=> null,
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'cardshop_cache'),
];
