<?php

return [

    'paytech' => [
        'api_key'    => env('PAYTECH_API_KEY'),
        'api_secret' => env('PAYTECH_API_SECRET'),
        'base_url'   => env('PAYTECH_BASE_URL', 'https://paytech.sn'),
        'currency'   => 'XOF',
        'timeout'    => 30,
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'supported_methods' => ['paytech', 'cash'],

];
