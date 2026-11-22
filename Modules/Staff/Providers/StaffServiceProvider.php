<?php

namespace Modules\Staff\Providers;

use App\Support\ModuleServiceProvider;

class StaffServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
