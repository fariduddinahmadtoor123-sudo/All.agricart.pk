<?php

namespace Modules\Marketplace\Providers;

use App\Support\ModuleServiceProvider;

class MarketplaceServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
