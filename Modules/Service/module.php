<?php

use Modules\Service\Providers\ServiceServiceProvider;

return [
    'key' => 'service',
    'label' => 'Service',
    'route' => 'service',
    'patterns' => [
        'service',
    ],
    'soon' => true,
    'provider' => ServiceServiceProvider::class,
];
