<?php

namespace Modules\Forex\Providers;

use App\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class ForexServiceProvider extends ModuleServiceProvider
{
    protected function routeFile(): string
    {
        return dirname(__DIR__).'/routes/web.php';
    }

    public function register(): void
    {
        parent::register();

        Route::middleware('api')
            ->prefix('api')
            ->group(dirname(__DIR__).'/routes/api.php');
    }
}
