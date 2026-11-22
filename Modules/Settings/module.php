<?php

use Modules\Settings\Providers\SettingsServiceProvider;

return [
    'key' => 'settings',
    'label' => 'Settings',
    'route' => 'settings',
    'patterns' => [
        'settings',
        'roles*',
        'colors*',
    ],
    'soon' => false,
    'provider' => SettingsServiceProvider::class,
];
