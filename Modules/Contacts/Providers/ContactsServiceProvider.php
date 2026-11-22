<?php

namespace Modules\Contacts\Providers;

use App\Support\ModuleServiceProvider;

class ContactsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
