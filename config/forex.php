<?php

/**
 * Desk Forex gate — max 15 symbols, grouped for the Trade Terminal sessions.
 */
return [
    'max_symbols' => 15,

    'categories' => [
        'forex' => [
            'label' => 'Forex',
            'symbols' => [
                'EURUSD',
                'GBPUSD',
                'USDJPY',
                'USDCHF',
                'AUDUSD',
                'NZDUSD',
                'USDCAD',
                'EURGBP',
            ],
        ],
        'commodities' => [
            'label' => 'Commodities',
            'symbols' => [
                'XAUUSD',
                'XAGUSD',
                'XTIUSD',
            ],
        ],
        'crypto' => [
            'label' => 'Crypto',
            'symbols' => [
                'BTCUSD',
                'ETHUSD',
            ],
        ],
        'indexes' => [
            'label' => 'Indexes',
            'symbols' => [
                'US30',
                'NAS100',
            ],
        ],
    ],
];
