<?php

use Modules\PersonalVault\Providers\PersonalVaultServiceProvider;

return [
    'key' => 'personal-vault',
    'label' => 'Personal Vault',
    'route' => 'personal-vault',
    'patterns' => [
        'personal-vault',
    ],
    'soon' => false,
    'provider' => PersonalVaultServiceProvider::class,
];
