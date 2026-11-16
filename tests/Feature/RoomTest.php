<?php

namespace Tests\Feature;

use App\Enums\RoomType;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rooms (FR-RM1, FR-RM2).
 */
class RoomTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Computer Lab 3',
            'code' => 'lab-3',
            'type' => 'computer_lab',
            'capacity' => 40,
            'location' => 'IT Building',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_everyone_can_browse_and_filter_rooms(): void
    {
        Room::factory()->create(['name' => 'Computer Lab 1', 'type' => RoomType::ComputerLab]);
        Room::factory()->create(['name' => 'Audio-Visual Room', 'type' => RoomType::Avr]);
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('rooms.index'))
            ->assertOk()->assertSee('Computer Lab 1')->assertSee('Audio-Visual Room')->assertDontSee('New Room');

        $this->actingAs($student)->get(route('rooms.index', ['type' => 'avr']))
            ->assertSee('Audio-Visual Room')->assertDontSee('Computer Lab 1');
    }

    public function test_admin_can_add_a_room_and_code_is_uppercased(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rooms.store'), $this->validData())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $room = Room::first();
        $this->assertSame('LAB-3', $room->code);
        $this->assertTrue($room->is_active);
    }

    public function test_unchecked_active_box_saves_the_room_as_inactive(): void
    {
        $data = $this->validData();
        unset($data['is_active']); // unchecked checkboxes are not sent at all

        $this->actingAs($this->admin())->post(route('admin.rooms.store'), $data);

        $this->assertFalse(Room::first()->is_active);
    }

    public function test_code_must_be_unique_and_type_valid(): void
    {
        Room::factory()->create(['code' => 'LAB-3']);

        $this->actingAs($this->admin())
            ->post(route('admin.rooms.store'), $this->validData(['type' => 'gym']))
            ->assertSessionHasErrors(['code', 'type']);
    }

    public function test_admin_can_update_a_room(): void
    {
        $room = Room::factory()->create(['code' => 'LAB-3']);

        $this->actingAs($this->admin())
            ->put(route('admin.rooms.update', $room), $this->validData(['name' => 'Renamed Lab']))
            ->assertRedirect(route('rooms.show', $room))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Lab', $room->fresh()->name);
    }

    public function test_room_with_active_reservations_cannot_be_deleted(): void
    {
        $room = Room::factory()->create();
        Reservation::factory()->for($room)->create(); // pending

        $this->actingAs($this->admin())
            ->delete(route('admin.rooms.destroy', $room))
            ->assertSessionHas('error');

        $this->assertModelExists($room);
    }

    public function test_room_with_only_past_reservations_can_be_deleted_and_history_is_kept(): void
    {
        $room = Room::factory()->create();
        $old = Reservation::factory()->for($room)->returned()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.rooms.destroy', $room))
            ->assertRedirect(route('rooms.index'));

        $this->assertModelMissing($room);
        $this->assertNull($old->fresh()->room_id); // reservation still exists, room link cleared
    }

    public function test_student_cannot_manage_rooms(): void
    {
        $student = User::factory()->create();
        $room = Room::factory()->create();

        $this->actingAs($student)->get(route('admin.rooms.create'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.rooms.store'), $this->validData())->assertForbidden();
        $this->actingAs($student)->delete(route('admin.rooms.destroy', $room))->assertForbidden();
    }

    public function test_room_page_shows_only_the_next_7_days(): void
    {
        $room = Room::factory()->create();
        $soon = now()->addDays(2)->setTime(9, 0);
        $later = now()->addDays(10)->setTime(9, 0);
        Reservation::factory()->for($room)->at($soon, $soon->copy()->addHours(2))->create(['purpose' => 'Within the week']);
        Reservation::factory()->for($room)->at($later, $later->copy()->addHours(2))->create(['purpose' => 'Too far ahead']);
        Reservation::factory()->for($room)->at($soon->copy()->addHours(3), $soon->copy()->addHours(4))->create([
            'purpose' => 'Cancelled one', 'status' => 'cancelled',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Within the week')
            ->assertDontSee('Too far ahead')
            ->assertDontSee('Cancelled one');
    }

    public function test_room_page_shows_who_is_using_it_now_without_email_for_students(): void
    {
        $room = Room::factory()->create();
        $borrower = User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);
        Reservation::factory()->for($room)->for($borrower)->at(now()->subHour(), now()->addHour())->approved()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('rooms.show', $room))
            ->assertSee('In use by Maria Santos')
            ->assertDontSee('maria@example.com');

        $this->actingAs($this->admin())
            ->get(route('rooms.show', $room))
            ->assertSee('maria@example.com');
    }
}
