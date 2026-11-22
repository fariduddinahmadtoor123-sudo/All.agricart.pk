<?php

use Modules\Logistics\Providers\LogisticsServiceProvider;

return [
    'key' => 'logistics',
    'label' => 'Logistics',
    'route' => 'logistics',
    'patterns' => [
        'logistics',
    ],
    'soon' => false,
    'provider' => LogisticsServiceProvider::class,
];
