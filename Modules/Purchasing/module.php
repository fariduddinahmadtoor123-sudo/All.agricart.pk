<?php

use Modules\Purchasing\Providers\PurchasingServiceProvider;

return [
    'key' => 'purchasing',
    'label' => 'Purchasing',
    'route' => 'purchasing',
    'patterns' => [
        'purchasing',
        'purchasing.*',
    ],
    'soon' => false,
    'provider' => PurchasingServiceProvider::class,
];
