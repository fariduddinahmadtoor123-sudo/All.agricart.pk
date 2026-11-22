<?php

namespace Modules\Catalog\Providers;

use App\Support\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
