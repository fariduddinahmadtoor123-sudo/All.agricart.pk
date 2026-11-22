<?php

namespace Modules\Purchasing\Providers;

use App\Support\ModuleServiceProvider;

class PurchasingServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
