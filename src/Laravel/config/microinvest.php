<?php

return [
    'default' => env('MICROINVEST_CONNECTION', 'local'),
    'connections' => [
        'local' => [
            'driver'   => 'warehouse_pro',
            'base_url' => env('MICROINVEST_BASE_URL', 'http://127.0.0.1:8700'),
            'api_key'  => env('MICROINVEST_API_KEY'),
            'timeout'  => (int) env('MICROINVEST_TIMEOUT', 30),
        ],

        // The hosted micro.bg service. Credentials come from
        // Администриране -> Връзка с ел. магазини -> Настройки.
        'online' => [
            'driver'      => 'micro_bg',
            'api_id'      => env('MICROBG_API_ID'),
            'secret_key'  => env('MICROBG_SECRET_KEY'),
            'entry_point' => env('MICROBG_ENTRY_POINT', 'https://micro.bg/ExtApps/ExternalApp/API/'),
            'timeout'     => (int) env('MICROBG_TIMEOUT', 30),
        ],
    ],
];
