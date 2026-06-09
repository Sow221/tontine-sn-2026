<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paramètres globaux de la plateforme TontineSN
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'TontineSN'),

    'transaction' => [
        'daily_limit'       => env('TRANSACTION_DAILY_LIMIT', 500000),  // FCFA
        'kyc_threshold'     => 300_000,   // KYC vérifié requis au-dessus de ce seuil
        'kyc_doc_threshold' => 50_000,    // Doc soumis requis au-dessus de ce seuil
        'min_amount'        => 500,
        'max_amount'        => 500_000,
        'reverse_window_h'  => 24,                                       // heures
    ],

    'otp' => [
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 5),
        'length'         => 6,
        'max_attempts'   => 3,
    ],

    'credit_score' => [
        'weight_amount'      => 0.3,
        'weight_punctuality' => 0.5,
        'weight_seniority'   => 0.2,
        'base_amount'        => 100_000,
        'seniority_base'     => 12,
        'badges' => [
            'bronze' => 4.0,
            'silver' => 6.5,
            'gold'   => 8.5,
        ],
    ],

    'tontine' => [
        'frequencies' => ['daily', 'weekly', 'monthly'],
        'types'       => ['fixed', 'auction', 'forced_saving', 'ceremonial'],
        'statuses'    => ['pending', 'active', 'completed', 'suspended'],
        'max_members' => 50,
        'min_members' => 2,
    ],

    'notifications' => [
        'reminder_days_before' => [3, 1],
        'overdue_days_after'   => [1, 3, 7],
    ],

    'languages' => ['fr'],

    // USSD : prévu en évolution — non exposé dans l'interface membre v1
    'ussd' => [
        'code'        => '*144#',
        'session_ttl' => 120,
        'enabled'     => false,
    ],

    'bceao' => [
        'retention_years'  => 5,
        'report_frequency' => 'quarterly',
    ],

];
