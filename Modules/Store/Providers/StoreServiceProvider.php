<?php

namespace Modules\Store\Providers;

use App\Support\ModuleServiceProvider;

class StoreServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
