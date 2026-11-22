<?php

namespace Modules\Service\Providers;

use App\Support\ModuleServiceProvider;

class ServiceServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
