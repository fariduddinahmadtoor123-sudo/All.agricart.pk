<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Sign in and Home opens.')
            ->assertSee('Forgot password?')
            ->assertSee('Remember me')
            ->assertDontSee('admin@gmail.com')
            ->assertDontSee('value="admin"', false);
    }

    public function test_the_admin_signs_in_with_a_password(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => 'admin',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('>Dashboard<', false)
            ->assertSee('Register')
            ->assertSee('The online store will appear here, inshallah.')
            ->assertDontSee('New system')
            ->assertDontSee('#F5EBE0');

        $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => 'admin',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee('Welcome, Admin.');
    }

    public function test_guest_is_sent_to_login_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_wrong_password_stays_on_the_login_page(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_user_reaches_the_dashboard_after_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee($user->name);
    }

    public function test_logout_returns_to_the_login_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
