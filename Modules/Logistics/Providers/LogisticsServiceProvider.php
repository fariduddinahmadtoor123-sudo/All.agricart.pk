<?php

namespace Modules\Logistics\Providers;

use App\Support\ModuleServiceProvider;

class LogisticsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
