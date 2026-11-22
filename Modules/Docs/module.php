<?php

use Modules\Docs\Providers\DocsServiceProvider;

return [
    'key' => 'docs',
    'label' => 'Docs',
    'route' => 'notes',
    'patterns' => [
        'notes*',
    ],
    'soon' => false,
    'provider' => DocsServiceProvider::class,
];
