<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_roles(): void
    {
        $this->get('/roles')->assertRedirect('/login');
    }

    public function test_settings_opens_roles_in_the_submenu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('href="'.route('roles').'"', false);
    }

    public function test_a_user_opens_the_roles_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/roles')
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('Add new role')
            ->assertSee('No roles yet.')
            ->assertSee('New tab');
    }

    public function test_a_saved_role_appears_on_the_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/roles', [
                'name' => 'Cashier',
                'description' => 'Takes payments.',
            ])
            ->assertRedirect('/roles');

        $this->actingAs($user)
            ->get('/roles')
            ->assertOk()
            ->assertSee('Cashier')
            ->assertSee('Takes payments.')
            ->assertSee('Settings')
            ->assertDontSee('<h1', false);
    }

    public function test_a_role_needs_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/roles')
            ->post('/roles', [
                'name' => '',
                'description' => 'Missing the name.',
            ])
            ->assertRedirect('/roles')
            ->assertSessionHasErrors('name');
    }

    public function test_guest_is_sent_to_login_from_the_new_role_page(): void
    {
        $this->get('/roles/create')->assertRedirect('/login');
    }

    public function test_a_user_opens_the_new_role_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/roles/create')
            ->assertOk()
            ->assertSee('New role')
            ->assertSee('Name')
            ->assertSee('Description');
    }
}
