<?php

namespace Tests\Fixtures;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class BrokenModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        throw new RuntimeException('This module failed to start.');
    }
}
