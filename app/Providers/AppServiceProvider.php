<?php

namespace App\Providers;

use App\Http\Controllers\MenuStubController;
use App\Support\DeskSubmenus;
use App\Support\ModuleRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        ModuleRegistry::boot();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        foreach (DeskSubmenus::stubRoutes() as $stub) {
            $middleware = ['web', 'auth'];

            if ($stub['super']) {
                $middleware[] = 'super';
            }

            Route::middleware($middleware)
                ->get($stub['path'], MenuStubController::class)
                ->defaults('deskTitle', $stub['title'])
                ->name($stub['route']);
        }
    }
}
