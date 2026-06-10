<?php

return [

    'paytech' => [
        'api_key' => env('PAYTECH_API_KEY'),
        'api_secret' => env('PAYTECH_API_SECRET'),
        'base_url' => env('PAYTECH_BASE_URL', 'https://paytech.sn'),
        'currency' => 'XOF',
        'timeout' => 30,
        'fee_bearer' => env('PAYTECH_FEE_BEARER', 'customer'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'supported_methods' => ['wave', 'orange_money', 'free_money', 'card', 'cash'],

];
