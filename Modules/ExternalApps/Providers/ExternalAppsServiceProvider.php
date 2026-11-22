<?php

namespace Modules\ExternalApps\Providers;

use App\Support\ModuleServiceProvider;

class ExternalAppsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
