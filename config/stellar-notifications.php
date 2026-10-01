<?php

return [
    'base_url' => env('STELLAR_NOTIFICATIONS_BASE_URL', ''),
    'basic_username' => env('STELLAR_NOTIFICATIONS_BASIC_USERNAME', ''),
    'basic_password' => env('STELLAR_NOTIFICATIONS_BASIC_PASSWORD', ''),
    'payout_details_url' => env('AFFILIATE_PAYOUT_DETAILS_URL', 'https://stellarafi.com/affiliate/payouts'),
    'timeout_seconds' => (int) env('STELLAR_NOTIFICATIONS_TIMEOUT', 45),
];
