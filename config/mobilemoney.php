<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration Mobile Money (Wave & Orange Money)
    |--------------------------------------------------------------------------
    */

    'wave' => [
        'api_key'        => env('WAVE_API_KEY'),
        'api_secret'     => env('WAVE_API_SECRET'),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
        'base_url'       => env('WAVE_BASE_URL', 'https://api.wave.com/v1'),
        'currency'       => 'XOF',
        'timeout'        => 30,
    ],

    'orange_money' => [
        'api_key'    => env('ORANGE_MONEY_API_KEY'),
        'api_secret' => env('ORANGE_MONEY_API_SECRET'),
        'base_url'   => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com/orange-money-webpay/sn/v1'),
        'currency'   => 'XOF',
        'timeout'    => 30,
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'sender'  => env('SMS_SENDER', 'TontineSN'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'supported_methods' => ['wave', 'orange_money', 'cash'],

];
