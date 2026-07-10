<?php

return [
    'default' => env('MICROINVEST_CONNECTION', 'local'),
    'connections' => [
        'local' => [
            'base_url' => env('MICROINVEST_BASE_URL', 'http://127.0.0.1:8700'),
            'api_key'  => env('MICROINVEST_API_KEY'),
            'timeout'  => (int) env('MICROINVEST_TIMEOUT', 30),
        ],
    ],
];
