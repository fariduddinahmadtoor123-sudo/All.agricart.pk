<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_forgot_password_and_password_toggle(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('Remember me')
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('aria-label="Show password"', false);
    }

    public function test_guest_can_open_the_forgot_password_page(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Forgot password')
            ->assertSee('Send reset link');
    }

    public function test_reset_link_is_sent_for_a_known_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
        ])->assertRedirect('/forgot-password')->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);

        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('data-password-toggle="password"', false);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertRedirect('/dashboard');
    }
}
