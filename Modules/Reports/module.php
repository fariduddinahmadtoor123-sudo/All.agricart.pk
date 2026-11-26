<?php

use Modules\Reports\Providers\ReportsServiceProvider;

return [
    'key' => 'reports',
    'label' => 'Reports',
    'route' => 'reports',
    'patterns' => [
        'reports',
        'reports.*',
    ],
    'soon' => false,
    'provider' => ReportsServiceProvider::class,
];
