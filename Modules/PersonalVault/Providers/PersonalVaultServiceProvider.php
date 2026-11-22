<?php

namespace Modules\PersonalVault\Providers;

use App\Support\ModuleServiceProvider;

class PersonalVaultServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
