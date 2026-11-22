<?php

namespace Modules\Approvals\Providers;

use App\Support\ModuleServiceProvider;

class ApprovalsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
