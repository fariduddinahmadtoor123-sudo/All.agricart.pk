<?php

use Modules\PersonalVault\Providers\PersonalVaultServiceProvider;

return [
    'key' => 'personal-vault',
    'label' => 'Forex Wallet',
    'route' => 'personal-vault',
    'patterns' => [
        'personal-vault',
        'personal-vault.*',
    ],
    'soon' => false,
    'provider' => PersonalVaultServiceProvider::class,
];
