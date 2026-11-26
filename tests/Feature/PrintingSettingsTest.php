<?php

namespace Tests\Feature;

use App\Models\PrintProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_printing_page_lists_iso_and_barcode_sizes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/printing')
            ->assertOk()
            ->assertSee('Printing')
            ->assertSee('A4 document')
            ->assertSee('A5 document')
            ->assertSee('50 × 30 mm barcode')
            ->assertSee('Labels & barcodes')
            ->assertSee('Add print size');
    }

    public function test_a_custom_document_size_can_be_added_and_previewed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/printing', [
            'name_en' => 'Half sheet',
            'name_ur' => 'نصف صفحہ',
            'category' => 'document',
            'width_mm' => 148,
            'height_mm' => 210,
            'margin_mm' => 8,
            'orientation' => 'portrait',
            'printer_hint' => 'Custom A5-like',
        ])->assertRedirect('/settings/printing');

        $profile = PrintProfile::query()->where('name_en', 'Half sheet')->firstOrFail();

        $this->actingAs($user)->get(route('settings.printing.preview', $profile))
            ->assertOk()
            ->assertSee('Half sheet')
            ->assertSee('window.print()', false);

        $this->actingAs($user)
            ->from('/settings/printing')
            ->post(route('settings.printing.default', $profile))
            ->assertRedirect('/settings/printing');

        $this->assertTrue($profile->fresh()->is_default);
    }

    public function test_barcode_preview_renders_code128(): void
    {
        $user = User::factory()->create();
        $profile = PrintProfile::query()->where('key', 'barcode_50x30')->firstOrFail();

        $this->actingAs($user)->get(route('settings.printing.barcode', $profile))
            ->assertOk()
            ->assertSee('svg', false)
            ->assertSee('DESK-');
    }

    public function test_system_profiles_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $profile = PrintProfile::query()->where('key', 'a4')->firstOrFail();

        $this->actingAs($user)
            ->from('/settings/printing')
            ->delete(route('settings.printing.destroy', $profile))
            ->assertRedirect('/settings/printing')
            ->assertSessionHasErrors('profile');

        $this->assertDatabaseHas('print_profiles', ['key' => 'a4']);
    }
}
