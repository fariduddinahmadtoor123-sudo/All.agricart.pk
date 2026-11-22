<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function routeFile(): string;

    public function register(): void
    {
        Route::middleware(['web', 'auth'])->group($this->routeFile());
    }
}
