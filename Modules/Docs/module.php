<?php

use Modules\Docs\Providers\DocsServiceProvider;

return [
    'key' => 'docs',
    'label' => 'Docs',
    'route' => 'notes',
    'patterns' => [
        'notes*',
        'docs',
        'docs.*',
    ],
    'soon' => false,
    'provider' => DocsServiceProvider::class,
];
