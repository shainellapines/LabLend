<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 3.4: GET /reservations/availability (live "unavailable" hints in the reservation form).
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function check(array $params, ?User $user = null)
    {
        return $this->actingAs($user ?? User::factory()->create())
            ->getJson(route('reservations.availability', $params));
    }

    public function test_lists_items_and_rooms_taken_in_the_slot(): void
    {
        $start = now()->addDay()->setTime(13, 0);
        $item = Equipment::factory()->create();
        $free = Equipment::factory()->create();
        $room = Room::factory()->create();
        $reservation = Reservation::factory()->for($room)->at($start, $start->copy()->addHours(2))->approved()->create(['reference_no' => 'RSV-2026-0600']);
        $reservation->equipment()->attach($item);

        $response = $this->check(['start_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'end_at' => $start->copy()->addHours(3)->format('Y-m-d\TH:i')]);

        $response->assertOk()
            ->assertJsonPath("equipment.{$item->id}", fn ($text) => str_contains($text, 'RSV-2026-0600'))
            ->assertJsonPath("rooms.{$room->id}", fn ($text) => str_contains($text, 'RSV-2026-0600'))
            ->assertJsonMissingPath("equipment.{$free->id}");
    }

    public function test_touching_slot_and_finished_reservations_are_free(): void
    {
        $start = now()->addDay()->setTime(13, 0);
        $item = Equipment::factory()->create();
        Reservation::factory()->at($start, $start->copy()->addHours(2))->approved()->create()->equipment()->attach($item);
        Reservation::factory()->at($start->copy()->addHours(2), $start->copy()->addHours(4))->create(['status' => 'cancelled'])->equipment()->attach($item);

        // 3:00–5:00 PM touches the approved one and overlaps only a cancelled one.
        $this->check(['start_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'), 'end_at' => $start->copy()->addHours(4)->format('Y-m-d\TH:i')])
            ->assertOk()
            ->assertExactJson(['equipment' => [], 'rooms' => []]);
    }

    public function test_reservation_being_edited_is_ignored(): void
    {
        $start = now()->addDay()->setTime(13, 0);
        $item = Equipment::factory()->create();
        $mine = Reservation::factory()->at($start, $start->copy()->addHours(2))->create();
        $mine->equipment()->attach($item);

        $this->check([
            'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'ignore' => $mine->id,
        ])->assertExactJson(['equipment' => [], 'rooms' => []]);
    }

    public function test_does_not_reveal_borrower_names(): void
    {
        $start = now()->addDay()->setTime(13, 0);
        $borrower = User::factory()->create(['name' => 'Secret Borrower']);
        Reservation::factory()->for($borrower)->for(Room::factory())->at($start, $start->copy()->addHour())->create();

        $response = $this->check(['start_at' => $start->format('Y-m-d\TH:i'), 'end_at' => $start->copy()->addHour()->format('Y-m-d\TH:i')]);

        $this->assertStringNotContainsString('Secret Borrower', $response->getContent());
    }

    public function test_invalid_input_gives_422_and_guests_are_rejected(): void
    {
        // Guest first (actingAs() below keeps the user logged in for the rest of the test).
        $this->getJson(route('reservations.availability', ['start_at' => now(), 'end_at' => now()->addHour()]))->assertUnauthorized();

        $this->check(['start_at' => 'nope'])->assertStatus(422)->assertJsonValidationErrors(['start_at', 'end_at']);
    }

    public function test_form_is_wired_to_the_endpoint(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reservations.create'))
            ->assertSee('reservationAvailability', false)
            ->assertSee(str_replace('/', '\/', route('reservations.availability')), false); // @js() escapes "/" as "\/"
    }
}
