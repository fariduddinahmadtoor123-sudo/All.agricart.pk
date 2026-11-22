<?php

namespace Tests\Feature;

use App\Models\ColorScheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_colors(): void
    {
        $this->get('/colors')->assertRedirect('/login');
    }

    public function test_a_user_opens_the_color_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/colors')
            ->assertOk()
            ->assertSee('Appearance')
            ->assertSee('Sand')
            ->assertSee('Forest')
            ->assertSee('Add new color')
            ->assertSee('Corners')
            ->assertSee('Square')
            ->assertSee('Soft')
            ->assertSee('Round')
            ->assertSee('Smooth')
            ->assertSee('Pill')
            ->assertSee('Open in new tab', false)
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee('Apply');
    }

    public function test_a_saved_color_scheme_appears_on_the_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/colors', [
                'name' => 'Dawn',
                'page' => 'f4e1d2',
                'card' => '#fff8f2',
                'soft' => '#ead5c4',
                'line' => '#d7bba6',
                'primary' => '#c4896a',
            ])
            ->assertRedirect('/colors');

        $this->actingAs($user)
            ->get('/colors')
            ->assertOk()
            ->assertSee('Dawn')
            ->assertSee('#f4e1d2');
    }

    public function test_a_color_scheme_needs_a_name_and_five_colors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/colors')
            ->post('/colors', [
                'name' => '',
                'page' => 'red',
            ])
            ->assertRedirect('/colors')
            ->assertSessionHasErrors(['name', 'page', 'card', 'soft', 'line', 'primary']);
    }

    public function test_guest_is_sent_to_login_from_the_new_color_page(): void
    {
        $this->get('/colors/create')->assertRedirect('/login');
    }

    public function test_a_user_opens_the_new_color_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/colors/create')
            ->assertOk()
            ->assertSee('New color')
            ->assertSee('Name')
            ->assertSee('Page')
            ->assertSee('Primary');
    }

    public function test_a_user_opens_the_edit_color_page(): void
    {
        $user = User::factory()->create();
        $scheme = ColorScheme::factory()->create(['name' => 'Dawn']);

        $this->actingAs($user)
            ->get(route('colors.edit', $scheme))
            ->assertOk()
            ->assertSee('Edit color')
            ->assertSee('Dawn')
            ->assertSeeInOrder(['Edit color', 'Dashboard']);
    }

    public function test_a_user_updates_a_color_scheme(): void
    {
        $user = User::factory()->create();
        $scheme = ColorScheme::factory()->create(['name' => 'Dawn']);

        $this->actingAs($user)
            ->put(route('colors.update', $scheme), [
                'name' => 'Dusk',
                'page' => '#112233',
                'card' => '#223344',
                'soft' => '#334455',
                'line' => '#445566',
                'primary' => '#556677',
            ])
            ->assertRedirect('/colors');

        $scheme->refresh();

        $this->assertSame('Dusk', $scheme->name);
        $this->assertSame('#112233', $scheme->page);
    }

    public function test_an_update_can_keep_the_same_name(): void
    {
        $user = User::factory()->create();
        $scheme = ColorScheme::factory()->create(['name' => 'Dawn', 'primary' => '#d5bdaf']);

        $this->actingAs($user)
            ->put(route('colors.update', $scheme), [
                'name' => 'Dawn',
                'page' => $scheme->page,
                'card' => $scheme->card,
                'soft' => $scheme->soft,
                'line' => $scheme->line,
                'primary' => '#83b735',
            ])
            ->assertRedirect('/colors');

        $this->assertSame('#83b735', $scheme->fresh()->primary);
    }

    public function test_an_update_rejects_another_schemes_name(): void
    {
        $user = User::factory()->create();
        ColorScheme::factory()->create(['name' => 'Dawn']);
        $scheme = ColorScheme::factory()->create(['name' => 'Dusk']);

        $this->actingAs($user)
            ->from('/colors')
            ->put(route('colors.update', $scheme), [
                'name' => 'Dawn',
                'page' => $scheme->page,
                'card' => $scheme->card,
                'soft' => $scheme->soft,
                'line' => $scheme->line,
                'primary' => $scheme->primary,
            ])
            ->assertRedirect('/colors')
            ->assertSessionHasErrors('name');

        $this->assertSame('Dusk', $scheme->fresh()->name);
    }

    public function test_an_invalid_update_leaves_the_color_scheme_unchanged(): void
    {
        $user = User::factory()->create();
        $scheme = ColorScheme::factory()->create(['name' => 'Dawn']);

        $this->actingAs($user)
            ->from('/colors')
            ->put(route('colors.update', $scheme), [
                'name' => '',
                'page' => 'red',
            ])
            ->assertRedirect('/colors')
            ->assertSessionHasErrors(['name', 'page', 'card', 'soft', 'line', 'primary']);

        $this->assertSame('Dawn', $scheme->fresh()->name);
    }

    public function test_a_user_deletes_a_color_scheme(): void
    {
        $user = User::factory()->create();
        $scheme = ColorScheme::factory()->create(['name' => 'Dawn']);

        $this->actingAs($user)
            ->delete(route('colors.destroy', $scheme))
            ->assertRedirect('/colors');

        $this->assertModelMissing($scheme);

        $this->actingAs($user)
            ->get('/colors')
            ->assertOk()
            ->assertDontSee('Dawn');
    }

    public function test_guest_is_sent_to_login_when_updating_a_color_scheme(): void
    {
        $scheme = ColorScheme::factory()->create();

        $this->put(route('colors.update', $scheme))->assertRedirect('/login');

        $this->assertModelExists($scheme);
    }

    public function test_guest_is_sent_to_login_when_deleting_a_color_scheme(): void
    {
        $scheme = ColorScheme::factory()->create();

        $this->delete(route('colors.destroy', $scheme))->assertRedirect('/login');

        $this->assertModelExists($scheme);
    }

    public function test_guest_is_sent_to_login_from_the_edit_color_page(): void
    {
        $scheme = ColorScheme::factory()->create();

        $this->get(route('colors.edit', $scheme))->assertRedirect('/login');
    }
}
