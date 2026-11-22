<?php

use Modules\ExternalApps\Providers\ExternalAppsServiceProvider;

return [
    'key' => 'external-apps',
    'label' => 'External Apps',
    'route' => 'external-apps',
    'patterns' => [
        'external-apps',
    ],
    'soon' => false,
    'provider' => ExternalAppsServiceProvider::class,
];
