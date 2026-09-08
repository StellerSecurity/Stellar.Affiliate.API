<?php

return [
    'enabled' => (bool) env('AFFILIATE_AUTOMATIC_PAYOUTS_ENABLED', false),
    // Required, UTC date (YYYY-MM-DD). First cutoff is start + 30 days.
    'starts_on' => env('AFFILIATE_PAYOUT_STARTS_ON', '2026-09-01'),
    'period_days' => 30,
    'delay_days' => 7,
    'minimum_cents' => 10000,
    'revolut' => [
        'environment' => env('REVOLUT_ENVIRONMENT', 'sandbox'),
        'source_account_id' => env('REVOLUT_SOURCE_ACCOUNT_ID'),
        'client_id' => env('REVOLUT_CLIENT_ID'),
        'issuer' => env('REVOLUT_JWT_ISSUER'),
        'private_key_path' => env('REVOLUT_PRIVATE_KEY_PATH'),
        'refresh_token' => env('REVOLUT_REFRESH_TOKEN'),
    ],
];
