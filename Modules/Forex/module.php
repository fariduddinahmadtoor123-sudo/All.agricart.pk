<?php

use Modules\Forex\Providers\ForexServiceProvider;

return [
    'key' => 'forex',
    'label' => 'Forex Trading',
    'route' => 'forex.market-watch',
    'patterns' => [
        'forex*',
    ],
    'soon' => false,
    'provider' => ForexServiceProvider::class,
];
