<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests for the 'role' and 'active' middleware (FR-U2, FR-U3).
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // There are no real admin pages yet, so register a fake one with the same middleware.
        Route::middleware(['web', 'auth', 'active', 'role:admin'])
            ->get('/admin/test-page', fn () => 'admin page');
    }

    public function test_student_gets_403_on_admin_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/test-page')
            ->assertForbidden();
    }

    public function test_faculty_gets_403_on_admin_routes(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get('/admin/test-page')
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/test-page')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/test-page')->assertRedirect(route('login'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => EnsureUserIsActive::MESSAGE]);
        $this->assertGuest();
    }

    public function test_user_deactivated_while_logged_in_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->forceFill(['is_active' => false])->save(); // what the admin will do in step 2.8

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_sees_admin_section_in_sidebar_and_student_does_not(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertSee('Categories');

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertDontSee('Categories');
    }
}
