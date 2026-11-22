<?php

namespace Modules\Settings\Providers;

use App\Support\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }
}
