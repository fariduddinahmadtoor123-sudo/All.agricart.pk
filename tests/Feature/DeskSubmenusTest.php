<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeskSubmenusTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_and_settings_show_old_agricart_submenus(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/catalog')
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Categories')
            ->assertSee('Brands');

        $this->actingAs($user)->get('/catalog/products')
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('Menu shell only');

        $this->actingAs($user)->get('/settings')
            ->assertOk()
            ->assertSee('AI settings')
            ->assertSee('Roles')
            ->assertSee('Appearance');

        $this->actingAs($user)->get('/settings/ai')
            ->assertOk()
            ->assertSee('AI settings')
            ->assertSee('Menu shell only');

        $this->actingAs($user)->get('/contacts/online-customers')
            ->assertOk()
            ->assertSee('Online customers');
    }
}
