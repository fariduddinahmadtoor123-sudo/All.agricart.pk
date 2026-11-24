<?php

namespace Modules\Forex\Providers;

use App\Models\User;
use App\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\Forex\Support\MarketWatch;

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

    public function boot(): void
    {
        View::composer('components.layouts.shell', function ($view): void {
            $user = auth()->user();
            $onTerminal = request()->routeIs('forex.market-watch');

            $view->with(
                'accountMenu',
                $onTerminal && $user instanceof User ? MarketWatch::menuFacts($user) : null,
            );
        });
    }
}
