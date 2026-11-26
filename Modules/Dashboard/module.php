<?php

use Modules\Dashboard\Providers\DashboardServiceProvider;

return [
    'key' => 'dashboard',
    'label' => 'Dashboard',
    'route' => 'dashboard',
    'patterns' => [
        'dashboard',
        'dashboard.*',
    ],
    'soon' => false,
    'provider' => DashboardServiceProvider::class,
];
