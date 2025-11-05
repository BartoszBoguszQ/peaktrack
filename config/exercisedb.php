<?php

return [
    'base_url' => env('EXERCISEDB_BASE_URL', 'https://exercisedb.dev/api'),
    'timeout' => (int) env('EXERCISEDB_TIMEOUT', 8),
    'limit' => (int) env('EXERCISEDB_LIMIT', 10),
    'api_key_header' => env('EXERCISEDB_API_KEY_HEADER', ''),
    'api_key' => env('EXERCISEDB_API_KEY', ''),
    'host' => env('EXERCISEDB_HOST', ''),
];
