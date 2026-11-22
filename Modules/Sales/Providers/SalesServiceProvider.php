<?php

namespace Modules\Sales\Providers;

use App\Support\ModuleServiceProvider;

class SalesServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
