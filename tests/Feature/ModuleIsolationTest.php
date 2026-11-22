<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\BrokenModuleServiceProvider;
use Tests\TestCase;

class ModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_module_can_fail_without_stopping_the_others(): void
    {
        $this->assertFalse(ModuleRegistry::registerProvider(BrokenModuleServiceProvider::class));

        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Welcome, '.$user->name);
        $this->actingAs($user)->get('/sales')->assertOk()->assertSee('Sales');
        $this->actingAs($user)->get('/purchasing')->assertOk()->assertSee('Purchasing');
    }
}
