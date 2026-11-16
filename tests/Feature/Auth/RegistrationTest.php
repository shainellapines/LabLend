<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'id_number' => '2024-12345',
            'department' => 'BSIT',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // New users always start as active students.
        $user = User::where('email', 'test@example.com')->first();
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_registration_cannot_set_its_own_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky User',
            'id_number' => '2024-99999',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin', // extra field a malicious user could add with DevTools
        ]);

        $this->assertSame(UserRole::Student, User::where('email', 'sneaky@example.com')->first()->role);
    }

    public function test_id_number_must_be_unique(): void
    {
        User::factory()->create(['id_number' => '2024-12345']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'id_number' => '2024-12345',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('id_number');
        $this->assertGuest();
    }
}
