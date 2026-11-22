<?php

namespace Modules\Finance\Providers;

use App\Support\ModuleServiceProvider;

class FinanceServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
