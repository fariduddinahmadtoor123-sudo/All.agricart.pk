<?php

use Modules\Contacts\Providers\ContactsServiceProvider;

return [
    'key' => 'contacts',
    'label' => 'Contacts',
    'route' => 'contacts',
    'patterns' => [
        'contacts',
        'contacts.*',
    ],
    'soon' => false,
    'provider' => ContactsServiceProvider::class,
];
