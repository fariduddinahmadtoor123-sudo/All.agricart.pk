<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_a_person_registers_and_cannot_enter_until_a_role_is_assigned(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Register')
            ->assertDontSee('Dashboard');

        $this->post(route('register'), $this->registration())
            ->assertRedirect(route('login'));

        $person = User::query()->where('email', 'sara@example.com')->first();
        $this->assertNotNull($person);
        $this->assertSame(User::STATUS_PENDING, $person->status);
        $this->assertNull($person->role_id);
        $this->assertNotNull($person->cnic_front_path);

        $this->from('/login')->post('/login', [
            'email' => 'sara@example.com',
            'password' => 'secret-pass',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();

        $admin = $this->superAdmin();
        $staff = Role::query()->create([
            'name' => 'Staff',
            'description' => 'Works inside Desk.',
        ]);

        $this->actingAs($admin)
            ->get(route('approvals'))
            ->assertOk()
            ->assertSee('Sara Khan')
            ->assertSee('Meezan Bank');

        $this->actingAs(User::factory()->create(['role_id' => $staff->id]))
            ->get(route('approvals'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('approvals.approve', $person), ['role_id' => $staff->id])
            ->assertRedirect(route('approvals'));

        $person->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $person->status);
        $this->assertSame($staff->id, $person->role_id);

        $this->post('/login', [
            'email' => 'sara@example.com',
            'password' => 'secret-pass',
        ])->assertRedirect('/dashboard');
    }

    public function test_a_rejection_needs_a_reason_and_the_same_person_can_send_it_again(): void
    {
        $this->post(route('register'), $this->registration())->assertRedirect(route('login'));
        $person = User::query()->where('email', 'sara@example.com')->firstOrFail();
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->from(route('approvals'))
            ->post(route('approvals.reject', $person), ['reason' => ''])
            ->assertRedirect(route('approvals'))
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post(route('approvals.reject', $person), ['reason' => 'The card photo is unclear.'])
            ->assertRedirect(route('approvals'));

        $this->post('/logout');

        $this->from('/login')->post('/login', [
            'email' => 'sara@example.com',
            'password' => 'secret-pass',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->post(route('register'), $this->registration())
            ->assertRedirect(route('login'));

        $this->assertSame(1, User::query()->where('email', 'sara@example.com')->count());
        $this->assertSame(User::STATUS_PENDING, $person->fresh()->status);
    }

    public function test_registration_without_the_identity_card_is_refused(): void
    {
        $payload = $this->registration();
        unset($payload['cnic_front'], $payload['cnic_back'], $payload['profile_photo']);

        $this->post(route('register'), $payload)
            ->assertSessionHasErrors(['profile_photo', 'cnic_front', 'cnic_back']);

        $this->assertSame(0, User::query()->where('email', 'sara@example.com')->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function registration(): array
    {
        return [
            'name' => 'Sara Khan',
            'name_ur' => 'سارہ خان',
            'email' => 'sara@example.com',
            'address' => '12 Market Road',
            'address_ur' => 'مارکیٹ روڈ',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'phones' => [[
                'contact_person' => 'Sara Khan',
                'contact_person_ur' => 'سارہ خان',
                'phone_number' => '03001234567',
            ]],
            'banks' => [[
                'bank_name' => 'Meezan Bank',
                'account_title' => 'Sara Khan',
                'account_title_ur' => 'سارہ خان',
                'account_number' => '123456789',
            ]],
            'profile_photo' => UploadedFile::fake()->image('photo.jpg'),
            'cnic_front' => UploadedFile::fake()->image('front.jpg'),
            'cnic_back' => UploadedFile::fake()->image('back.jpg'),
            'terms' => '1',
        ];
    }

    private function superAdmin(): User
    {
        $role = Role::query()->where('name', 'Super admin')->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
