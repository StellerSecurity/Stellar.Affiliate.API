<?php

return [
    'self_register_enabled' => filter_var(env('AFFILIATE_SELF_REGISTER_ENABLED', true), FILTER_VALIDATE_BOOL),

    'support_email' => env('AFFILIATE_SUPPORT_EMAIL', 'info@stellarsecurity.com'),

    // Legacy tracking links predate product tagging. Those links are eSIM links.
    'legacy_default_product' => 'esim',
    'legacy_unassigned_products' => ['', 'legacy', 'unknown', 'unassigned', 'null', 'none', 'n/a', 'na'],

    'admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('AFFILIATE_ADMIN_EMAILS', ''))
    ))),

    'products' => [
        'vpn' => [
            'label' => 'Stellar VPN',
            'default_redirect_url' => 'https://stellarvpn.org/',
            'aliases' => ['vpn', 'stellar_vpn', 'stellar-vpn', 'stellar vpn'],
            'rates' => [
                'initial' => 1.00,
                'recurring' => 0.60,
            ],
        ],
        'antivirus' => [
            'label' => 'Stellar Antivirus',
            'default_redirect_url' => 'https://stellarsecurity.com/stellar-antivirus',
            'aliases' => ['antivirus', 'stellar_antivirus', 'stellar-antivirus', 'stellar antivirus'],
            'rates' => [
                'initial' => 1.00,
                'recurring' => 0.60,
            ],
        ],
        'esim' => [
            'label' => 'Stellar eSIM',
            'default_redirect_url' => 'https://stellarsecurity.com/stellar-esim',
            'aliases' => ['esim', 'e_sim', 'stellar_esim', 'stellar-esim', 'stellar esim'],
            'rates' => [
                'initial' => 0.10,
                'recurring' => 0.10,
            ],
        ],
    ],

    'fallback_rates' => [
        'initial' => (float) env('AFFILIATE_INITIAL_RATE', 1.00),
        'recurring' => (float) env('AFFILIATE_RECURRING_RATE', 0.60),
    ],

    'resources' => [
        'esim_feed_url' => env(
            'AFFILIATE_ESIM_FEED_URL',
            'https://stellarsecurity.com/assets/esim/products.index.json'
        ),
    ],
];
