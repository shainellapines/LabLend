<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-E4: QR code on the item page and the printable label sheet (PDF).
 */
class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_page_shows_a_qr_code_with_its_url(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0004']);

        $this->actingAs(User::factory()->create())
            ->get(route('equipment.show', $item))
            ->assertOk()
            ->assertSee('QR asset tag')
            ->assertSee('<svg', false)
            ->assertSee('aria-label="QR code for ' . url('/equipment/PRJ-0004') . '"', false)
            ->assertDontSee('Print label'); // students cannot print labels
    }

    public function test_admin_sees_the_print_label_link(): void
    {
        $item = Equipment::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('equipment.show', $item))
            ->assertSee('Print label');
    }

    public function test_label_sheet_pdf_for_a_category(): void
    {
        $category = Category::factory()->create();
        Equipment::factory()->count(4)->for($category)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.equipment.qr-labels.pdf', ['category' => $category->id]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_label_sheet_pdf_for_selected_items(): void
    {
        $items = Equipment::factory()->count(2)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.equipment.qr-labels.pdf', ['ids' => $items->pluck('id')->all()]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_something_must_be_selected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.equipment.qr-labels.pdf'))
            ->assertSessionHasErrors('category');
    }

    public function test_label_selection_page_lists_items(): void
    {
        Equipment::factory()->create(['asset_tag' => 'LAP-0001']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.equipment.qr-labels'))
            ->assertOk()
            ->assertSee('LAP-0001');
    }

    public function test_students_cannot_print_labels(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.equipment.qr-labels'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.equipment.qr-labels.pdf', ['category' => Category::factory()->create()->id]))->assertForbidden();
    }
}
