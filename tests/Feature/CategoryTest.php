<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Categories CRUD (FR-C1).
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_see_the_category_list(): void
    {
        Category::factory()->create(['name' => 'Projectors', 'code' => 'PRJ']);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Projectors')
            ->assertSee('PRJ');
    }

    public function test_search_filters_the_list(): void
    {
        Category::factory()->create(['name' => 'Projectors', 'code' => 'PRJ']);
        Category::factory()->create(['name' => 'Laptops', 'code' => 'LAP']);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.index', ['search' => 'lap']))
            ->assertSee('Laptops')
            ->assertDontSee('Projectors');
    }

    public function test_admin_can_create_a_category_and_code_is_uppercased(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Projectors', 'code' => ' prj '])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => 'Projectors', 'code' => 'PRJ']);
    }

    public function test_name_and_code_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Projectors', 'code' => 'PRJ']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Projectors', 'code' => 'prj'])
            ->assertSessionHasErrors(['name', 'code']);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_code_only_allows_letters_and_numbers(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Cables', 'code' => 'CB-1'])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_a_category_without_triggering_its_own_unique_rule(): void
    {
        $category = Category::factory()->create(['name' => 'Projectors', 'code' => 'PRJ']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name' => 'Projectors',
                'code' => 'PRJ',
                'description' => 'Updated',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated', $category->fresh()->description);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertModelMissing($category);
    }

    public function test_student_cannot_manage_categories(): void
    {
        $student = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.categories.store'), ['name' => 'X', 'code' => 'X'])->assertForbidden();
        $this->actingAs($student)->delete(route('admin.categories.destroy', $category))->assertForbidden();

        $this->assertModelExists($category);
    }
}
