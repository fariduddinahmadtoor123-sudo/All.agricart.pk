<?php

use Modules\Sales\Providers\SalesServiceProvider;

return [
    'key' => 'sales',
    'label' => 'Sales',
    'route' => 'sales',
    'patterns' => [
        'sales',
        'sales.*',
    ],
    'soon' => false,
    'provider' => SalesServiceProvider::class,
];
