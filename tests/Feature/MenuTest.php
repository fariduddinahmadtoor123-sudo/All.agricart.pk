<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_side_menu_lists_each_section_once(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSeeInOrder([
            'Dashboard',
            'Approvals',
            'Contacts',
            'Catalog',
            'Purchasing',
            'Sales',
            'Store',
            'Logistics',
            'Service',
            'Soon',
            'Reports',
            'Staff',
            'Finance',
            'Marketplace',
            'Docs',
            'Personal Vault',
            'Forex Trading',
            'External Apps',
            'Settings',
        ])->assertDontSee('Home')
            ->assertDontSee('FX Learn')
            ->assertSee('Menus above')
            ->assertSee('Menus below');

        $this->assertSame(1, substr_count($response->getContent(), 'Settings'));
    }

    public function test_a_menu_section_opens_in_the_desk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/approvals')
            ->assertOk()
            ->assertSee('Approvals');

        $this->actingAs($user)
            ->get('/purchasing')
            ->assertOk()
            ->assertSee('Purchasing');
    }

    public function test_an_unknown_section_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/section/missing')->assertNotFound();
        $this->actingAs($user)->get('/fx-learn')->assertNotFound();
    }

    public function test_a_guest_is_sent_to_login_from_a_section(): void
    {
        $this->get('/approvals')->assertRedirect('/login');
    }
}
