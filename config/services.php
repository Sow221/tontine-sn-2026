<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'greenapi' => [
        'id_instance' => env('GREENAPI_ID_INSTANCE'),
        'api_token'   => env('GREENAPI_API_TOKEN'),
        'api_url'     => env('GREENAPI_API_URL', 'https://7107.api.greenapi.com'),
        'media_url'   => env('GREENAPI_MEDIA_URL', 'https://7107.api.greenapi.com'),
    ],

    'greenapi' => [
        'id_instance'     => env('GREENAPI_ID_INSTANCE'),
        'api_token'       => env('GREENAPI_API_TOKEN'),
        'api_url'         => env('GREENAPI_API_URL', 'https://api.greenapi.com'),
        'webhook_url'     => env('GREENAPI_WEBHOOK_URL'),
    ],

    'webhook_outbound' => [
        'url'    => env('WEBHOOK_OUTBOUND_URL'),
        'secret' => env('WEBHOOK_OUTBOUND_SECRET'),
    ],

];
