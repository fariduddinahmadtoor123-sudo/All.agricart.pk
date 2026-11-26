<?php

use Modules\Marketplace\Providers\MarketplaceServiceProvider;

return [
    'key' => 'marketplace',
    'label' => 'Marketplace',
    'route' => 'marketplace',
    'patterns' => [
        'marketplace',
        'marketplace.*',
    ],
    'soon' => false,
    'provider' => MarketplaceServiceProvider::class,
];
