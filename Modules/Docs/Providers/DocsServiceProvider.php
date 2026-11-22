<?php

namespace Modules\Docs\Providers;

use App\Support\ModuleServiceProvider;

class DocsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
