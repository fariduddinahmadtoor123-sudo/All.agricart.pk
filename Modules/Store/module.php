<?php

use Modules\Store\Providers\StoreServiceProvider;

return [
    'key' => 'store',
    'label' => 'Store',
    'route' => 'store',
    'patterns' => [
        'store',
        'store.*',
    ],
    'soon' => false,
    'provider' => StoreServiceProvider::class,
];
