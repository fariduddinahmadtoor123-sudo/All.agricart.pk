<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_notes(): void
    {
        $this->get('/notes')->assertRedirect('/login');
    }

    public function test_a_user_opens_saved_notes_from_docs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/notes')
            ->assertOk()
            ->assertSee('Docs')
            ->assertSee('Notes')
            ->assertSee('Add new note')
            ->assertSee('End of day')
            ->assertSee('اسے گٹ ہب پہ پش کریں۔ چیک کریں، اگر کوئی غلطی یا بگ ہو تو اسے ٹھیک کریں، اور ٹیگ لگا دیں۔')
            ->assertSee('Copy')
            ->assertSee('Edit')
            ->assertSee('Delete');
    }

    public function test_a_saved_note_appears_on_the_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/notes', [
                'title' => 'Morning check',
                'body' => 'ریٹ چیک کریں۔',
            ])
            ->assertRedirect('/notes');

        $this->actingAs($user)
            ->get('/notes')
            ->assertOk()
            ->assertSee('Morning check')
            ->assertSee('ریٹ چیک کریں۔');
    }

    public function test_a_note_needs_a_title_and_text(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/notes')
            ->post('/notes', [
                'title' => '',
                'body' => '',
            ])
            ->assertRedirect('/notes')
            ->assertSessionHasErrors(['title', 'body']);
    }

    public function test_guest_is_sent_to_login_from_the_new_note_page(): void
    {
        $this->get('/notes/create')->assertRedirect('/login');
    }

    public function test_a_user_opens_the_new_note_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/notes/create')
            ->assertOk()
            ->assertSee('New note')
            ->assertSee('Title')
            ->assertSee('Text');
    }

    public function test_a_user_opens_the_edit_note_page(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'title' => 'Morning check',
            'body' => 'ریٹ چیک کریں۔',
        ]);

        $this->actingAs($user)
            ->get(route('notes.edit', $note))
            ->assertOk()
            ->assertSee('Edit note')
            ->assertSee('Morning check')
            ->assertSee('ریٹ چیک کریں۔');
    }

    public function test_a_user_updates_a_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['title' => 'Morning check']);

        $this->actingAs($user)
            ->put(route('notes.update', $note), [
                'title' => 'Evening check',
                'body' => 'دن بند کریں۔',
            ])
            ->assertRedirect('/notes');

        $note->refresh();

        $this->assertSame('Evening check', $note->title);
        $this->assertSame('دن بند کریں۔', $note->body);
    }

    public function test_an_invalid_update_leaves_the_note_unchanged(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'title' => 'Morning check',
            'body' => 'ریٹ چیک کریں۔',
        ]);

        $this->actingAs($user)
            ->from('/notes')
            ->put(route('notes.update', $note), [
                'title' => '',
                'body' => '',
            ])
            ->assertRedirect('/notes')
            ->assertSessionHasErrors(['title', 'body']);

        $this->assertSame('Morning check', $note->fresh()->title);
    }

    public function test_a_user_deletes_a_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['title' => 'Morning check']);

        $this->actingAs($user)
            ->delete(route('notes.destroy', $note))
            ->assertRedirect('/notes');

        $this->assertModelMissing($note);
    }

    public function test_guest_is_sent_to_login_when_updating_a_note(): void
    {
        $note = Note::factory()->create();

        $this->put(route('notes.update', $note))->assertRedirect('/login');

        $this->assertModelExists($note);
    }

    public function test_guest_is_sent_to_login_when_deleting_a_note(): void
    {
        $note = Note::factory()->create();

        $this->delete(route('notes.destroy', $note))->assertRedirect('/login');

        $this->assertModelExists($note);
    }

    public function test_guest_is_sent_to_login_from_the_edit_note_page(): void
    {
        $note = Note::factory()->create();

        $this->get(route('notes.edit', $note))->assertRedirect('/login');
    }
}
