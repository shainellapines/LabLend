<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-U2 (admin user management) and FR-U3 (role-based access).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Lab Custodian']);
    }

    private function editPayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'id_number' => $user->id_number,
            'department' => $user->department,
            'phone' => $user->phone,
            'role' => $user->role->value,
        ], $overrides);
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        User::factory()->create(['name' => 'Juan Dela Cruz', 'id_number' => '2024-00001']);
        User::factory()->faculty()->create(['name' => 'Maria Santos']);
        User::factory()->inactive()->create(['name' => 'Old Student']);

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee('Juan Dela Cruz')->assertSee('Maria Santos');

        $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => '2024-00001']))
            ->assertSee('Juan Dela Cruz')->assertDontSee('Maria Santos');

        $this->actingAs($this->admin)->get(route('admin.users.index', ['role' => 'faculty']))
            ->assertSee('Maria Santos')->assertDontSee('Juan Dela Cruz');

        $this->actingAs($this->admin)->get(route('admin.users.index', ['active' => '0']))
            ->assertSee('Old Student')->assertDontSee('Juan Dela Cruz');
    }

    public function test_user_page_shows_overdue_count(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->overdue()->create(['reference_no' => 'RSV-2026-0077']);

        $this->actingAs($this->admin)->get(route('admin.users.show', $student))
            ->assertOk()->assertSee('RSV-2026-0077')->assertSee('Overdue');
    }

    public function test_admin_can_change_a_users_role_and_details(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $student), $this->editPayload($student, ['role' => 'faculty', 'department' => 'IT Department']))
            ->assertRedirect(route('admin.users.show', $student))
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame(UserRole::Faculty, $student->role);
        $this->assertSame('IT Department', $student->department);
    }

    public function test_email_and_id_number_stay_unique(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com', 'id_number' => '2024-99999']);
        $student = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $student), $this->editPayload($student, ['email' => $other->email, 'id_number' => $other->id_number]))
            ->assertSessionHasErrors(['email', 'id_number']);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->admin), $this->editPayload($this->admin, ['role' => 'student']))
            ->assertSessionHasErrors(['role' => 'You cannot remove your own admin role.']);

        $this->assertSame(UserRole::Admin, $this->admin->fresh()->role);
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)->patch(route('admin.users.toggle-active', $student))->assertSessionHas('success');
        $this->assertFalse($student->fresh()->is_active);

        // The deactivated user is logged out on their next request and cannot log in.
        $this->actingAs($student->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post('/login', ['email' => $student->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->admin)->patch(route('admin.users.toggle-active', $student));
        $this->assertTrue($student->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-active', $this->admin))->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_non_admins_get_403_on_user_management(): void
    {
        $faculty = User::factory()->faculty()->create();
        $student = User::factory()->create();

        $this->actingAs($faculty)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($faculty)->get(route('admin.users.edit', $student))->assertForbidden();
        $this->actingAs($faculty)->put(route('admin.users.update', $student), $this->editPayload($student, ['role' => 'admin']))->assertForbidden();
        $this->actingAs($faculty)->patch(route('admin.users.toggle-active', $student))->assertForbidden();

        $this->assertSame(UserRole::Student, $student->fresh()->role);
    }
}
