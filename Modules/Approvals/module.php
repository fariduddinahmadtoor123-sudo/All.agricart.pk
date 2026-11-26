<?php

use Modules\Approvals\Providers\ApprovalsServiceProvider;

return [
    'key' => 'approvals',
    'label' => 'Approvals',
    'route' => 'approvals',
    'patterns' => [
        'approvals',
        'approvals.*',
    ],
    'soon' => false,
    'provider' => ApprovalsServiceProvider::class,
];
