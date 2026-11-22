<?php

use Modules\Reports\Providers\ReportsServiceProvider;

return [
    'key' => 'reports',
    'label' => 'Reports',
    'route' => 'reports',
    'patterns' => [
        'reports',
    ],
    'soon' => false,
    'provider' => ReportsServiceProvider::class,
];
