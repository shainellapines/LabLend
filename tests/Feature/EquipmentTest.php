<?php

namespace Tests\Feature;

use App\Enums\EquipmentStatus;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AssetTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Equipment inventory (FR-E1, E2, E5, E6).
 */
class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function validData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'asset_tag' => 'prj-0001',
            'name' => 'Epson Projector',
            'brand' => 'Epson',
            'condition' => 'good',
            'status' => 'available',
        ], $overrides);
    }

    // ---------- List (everyone) ----------

    public function test_student_can_see_the_list_but_not_admin_buttons(): void
    {
        Equipment::factory()->create(['name' => 'Epson Projector', 'asset_tag' => 'PRJ-0001']);

        $this->actingAs(User::factory()->create())
            ->get(route('equipment.index'))
            ->assertOk()
            ->assertSee('Epson Projector')
            ->assertSee('PRJ-0001')
            ->assertDontSee('New Item')
            ->assertDontSee(route('admin.equipment.create'));
    }

    public function test_list_can_be_searched_and_filtered(): void
    {
        $projectors = Category::factory()->create();
        Equipment::factory()->for($projectors)->create(['name' => 'Epson Projector', 'serial_number' => 'SN-ABC']);
        Equipment::factory()->create(['name' => 'Lenovo Laptop']);
        Equipment::factory()->for($projectors)->retired()->create(['name' => 'Old Projector']);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('equipment.index', ['search' => 'SN-ABC']))
            ->assertSee('Epson Projector')->assertDontSee('Lenovo Laptop');

        $this->actingAs($user)->get(route('equipment.index', ['category' => $projectors->id]))
            ->assertSee('Epson Projector')->assertSee('Old Projector')->assertDontSee('Lenovo Laptop');

        $this->actingAs($user)->get(route('equipment.index', ['status' => 'retired']))
            ->assertSee('Old Projector')->assertDontSee('Epson Projector');
    }

    // ---------- Asset tag suggestion ----------

    public function test_next_asset_tag_continues_after_the_highest_including_deleted_items(): void
    {
        $category = Category::factory()->create(['code' => 'PRJ']);
        Equipment::factory()->for($category)->create(['asset_tag' => 'PRJ-0001']);
        Equipment::factory()->for($category)->create(['asset_tag' => 'PRJ-0004'])->delete(); // soft-deleted

        $this->assertSame('PRJ-0005', app(AssetTagService::class)->nextFor($category));
        $this->assertSame('LAP-0001', app(AssetTagService::class)->nextFor(Category::factory()->create(['code' => 'LAP'])));
    }

    // ---------- Create / update ----------

    public function test_admin_can_add_an_item_with_a_photo(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.equipment.store'), $this->validData($category, [
                'photo' => UploadedFile::fake()->image('projector.jpg'),
            ]))
            ->assertRedirect('/equipment/PRJ-0001')
            ->assertSessionHas('success');

        $item = Equipment::first();
        $this->assertSame('PRJ-0001', $item->asset_tag); // uppercased
        Storage::disk('public')->assertExists($item->photo_path);
    }

    public function test_photo_must_be_an_image_of_at_most_2_mb(): void
    {
        $category = Category::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.equipment.store'), $this->validData($category, [
                'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');

        $this->actingAs($admin)
            ->post(route('admin.equipment.store'), $this->validData($category, [
                'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000), // 3 MB
            ]))
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('equipment', 0);
    }

    public function test_asset_tag_must_be_unique_even_against_deleted_items(): void
    {
        $category = Category::factory()->create();
        Equipment::factory()->create(['asset_tag' => 'PRJ-0001'])->delete();

        $this->actingAs($this->admin())
            ->post(route('admin.equipment.store'), $this->validData($category))
            ->assertSessionHasErrors('asset_tag');
    }

    public function test_replacing_the_photo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('equipment', 'public');
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0001', 'photo_path' => $oldPath]);

        $this->actingAs($this->admin())
            ->put(route('admin.equipment.update', $item), $this->validData($item->category, [
                'name' => 'Renamed Projector',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertRedirect('/equipment/PRJ-0001');

        $item->refresh();
        $this->assertSame('Renamed Projector', $item->name);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($item->photo_path);
    }

    // ---------- Delete ----------

    public function test_delete_is_a_soft_delete(): void
    {
        $item = Equipment::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.equipment.destroy', $item))
            ->assertRedirect(route('equipment.index'));

        $this->assertSoftDeleted($item);
    }

    public function test_item_with_an_active_reservation_cannot_be_deleted(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->approved()->create()->equipment()->attach($item);

        $this->actingAs($this->admin())
            ->delete(route('admin.equipment.destroy', $item))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($item);
    }

    public function test_item_with_only_finished_reservations_can_be_deleted(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->returned()->create()->equipment()->attach($item);

        $this->actingAs($this->admin())->delete(route('admin.equipment.destroy', $item));

        $this->assertSoftDeleted($item);
    }

    // ---------- Access ----------

    public function test_student_cannot_create_edit_or_delete_equipment(): void
    {
        $student = User::factory()->create();
        $item = Equipment::factory()->create();

        $this->actingAs($student)->get(route('admin.equipment.create'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.equipment.edit', $item))->assertForbidden();
        $this->actingAs($student)->delete(route('admin.equipment.destroy', $item))->assertForbidden();

        $this->assertNotSoftDeleted($item);
    }

    public function test_admin_create_form_suggests_asset_tags(): void
    {
        $category = Category::factory()->create(['code' => 'PRJ']);
        Equipment::factory()->for($category)->create(['asset_tag' => 'PRJ-0003', 'status' => EquipmentStatus::Available]);

        $this->actingAs($this->admin())
            ->get(route('admin.equipment.create'))
            ->assertOk()
            ->assertSee('PRJ-0004');
    }
}
