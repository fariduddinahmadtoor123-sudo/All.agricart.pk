<?php

use Modules\Finance\Providers\FinanceServiceProvider;

return [
    'key' => 'finance',
    'label' => 'Finance',
    'route' => 'finance',
    'patterns' => [
        'finance',
        'finance.*',
    ],
    'soon' => false,
    'provider' => FinanceServiceProvider::class,
];
