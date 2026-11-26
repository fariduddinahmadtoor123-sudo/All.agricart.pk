<?php

use Modules\Service\Providers\ServiceServiceProvider;

return [
    'key' => 'service',
    'label' => 'Service',
    'route' => 'service',
    'patterns' => [
        'service',
        'service.*',
    ],
    'soon' => true,
    'provider' => ServiceServiceProvider::class,
];
