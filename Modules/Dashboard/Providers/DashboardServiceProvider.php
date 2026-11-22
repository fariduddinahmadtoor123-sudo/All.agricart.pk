<?php

namespace Modules\Dashboard\Providers;

use App\Support\ModuleServiceProvider;

class DashboardServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
