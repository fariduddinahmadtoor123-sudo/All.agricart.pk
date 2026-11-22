<?php

namespace Modules\Reports\Providers;

use App\Support\ModuleServiceProvider;

class ReportsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
