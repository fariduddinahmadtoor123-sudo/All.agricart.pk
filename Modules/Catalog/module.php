<?php

use Modules\Catalog\Providers\CatalogServiceProvider;

return [
    'key' => 'catalog',
    'label' => 'Catalog',
    'route' => 'catalog',
    'patterns' => [
        'catalog',
        'catalog.*',
    ],
    'soon' => false,
    'provider' => CatalogServiceProvider::class,
];
