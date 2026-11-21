<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_a_user_opens_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Home')
            ->assertSee('Forex')
            ->assertSee('Colors');
    }
}
