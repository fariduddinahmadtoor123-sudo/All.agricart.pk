<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_contacts(): void
    {
        $this->get('/contacts')->assertRedirect('/login');
        $this->get('/contacts/suppliers')->assertRedirect('/login');
        $this->get('/contacts/customers')->assertRedirect('/login');
        $this->get('/contacts/carriers')->assertRedirect('/login');
        $this->get('/contacts/message-templates')->assertRedirect('/login');
    }

    public function test_contacts_menus_and_form_previews_open(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/contacts')
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Suppliers')
            ->assertSee('Customers')
            ->assertSee('Online customers')
            ->assertSee('Carriers')
            ->assertSee('Message templates')
            ->assertSee('UI preview from the old Agricart Contacts cluster');

        $this->actingAs($user)->get('/contacts/suppliers')
            ->assertOk()
            ->assertSee('Add supplier')
            ->assertSee('Manufacturer')
            ->assertSee('UI preview only');

        $this->actingAs($user)->get('/contacts/customers')
            ->assertOk()
            ->assertSee('Add customer')
            ->assertSee('Display name');

        $this->actingAs($user)->get('/contacts/carriers')
            ->assertOk()
            ->assertSee('Add carrier')
            ->assertSee('Courier Company');

        $this->actingAs($user)->get('/contacts/message-templates')
            ->assertOk()
            ->assertSee('Add template')
            ->assertSee('Message (UR)');
    }
}
