<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForexTradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_forex_trading(): void
    {
        $this->get('/forex')->assertRedirect('/login');
    }

    public function test_a_user_opens_forex_trading(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/forex')
            ->assertOk()
            ->assertSee('Forex')
            ->assertSee('Home')
            ->assertDontSee('Store');
    }
}
