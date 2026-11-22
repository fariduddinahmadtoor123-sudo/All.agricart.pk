<?php

use Modules\Staff\Providers\StaffServiceProvider;

return [
    'key' => 'staff',
    'label' => 'Staff',
    'route' => 'staff',
    'patterns' => [
        'staff',
    ],
    'soon' => false,
    'provider' => StaffServiceProvider::class,
];
