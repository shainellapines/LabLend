<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Creating and editing reservations: FR-R1 (create), FR-R2 (conflict check), FR-R3 (borrowing rules), FR-R9 (edit).
 */
class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $start;

    protected function setUp(): void
    {
        parent::setUp();
        $this->start = now()->addDay()->setTime(13, 0); // tomorrow 1:00 PM
    }

    /** Form data for a reservation tomorrow 1:00–3:00 PM. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'purpose' => 'Class presentation',
            'start_at' => $this->start->format('Y-m-d\TH:i'),
            'end_at' => $this->start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'equipment_ids' => [],
            'room_id' => null,
        ], $overrides);
    }

    /** An existing active reservation of $item at the given time. */
    private function existingReservation(Equipment $item, Carbon $start, Carbon $end, string $status = 'approved'): Reservation
    {
        $reservation = Reservation::factory()->at($start, $end)->create(['status' => $status]);
        $reservation->equipment()->attach($item);

        return $reservation;
    }

    // ================= FR-R1: create =================

    public function test_student_can_reserve_items_and_a_room(): void
    {
        $student = User::factory()->create();
        $items = Equipment::factory()->count(2)->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($student)->post(route('reservations.store'), $this->payload([
            'equipment_ids' => $items->pluck('id')->all(),
            'room_id' => $room->id,
        ]));

        $reservation = Reservation::first();
        $response->assertRedirect(route('reservations.show', $reservation))->assertSessionHas('success');

        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertTrue($reservation->user->is($student));
        $this->assertTrue($reservation->room->is($room));
        $this->assertCount(2, $reservation->equipment);
        $this->assertMatchesRegularExpression('/^RSV-\d{4}-\d{4}$/', $reservation->reference_no);
    }

    public function test_reference_numbers_are_unique_and_increase(): void
    {
        $student = User::factory()->create();
        $item = Equipment::factory()->create();

        $this->actingAs($student)->post(route('reservations.store'), $this->payload(['equipment_ids' => [$item->id]]));
        $this->actingAs($student)->post(route('reservations.store'), $this->payload([
            'equipment_ids' => [$item->id],
            'start_at' => $this->start->copy()->addHours(3)->format('Y-m-d\TH:i'),
            'end_at' => $this->start->copy()->addHours(4)->format('Y-m-d\TH:i'),
        ]));

        [$first, $second] = Reservation::orderBy('id')->pluck('reference_no')->all();
        $this->assertSame(sprintf('RSV-%d-0001', now()->year), $first);
        $this->assertSame(sprintf('RSV-%d-0002', now()->year), $second);
    }

    public function test_at_least_one_item_or_a_room_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload())
            ->assertSessionHasErrors(['equipment_ids' => 'Select at least one item or a room.']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_room_only_reservation_is_allowed(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload(['room_id' => Room::factory()->create()->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_items_under_maintenance_and_inactive_rooms_cannot_be_reserved(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload([
                'equipment_ids' => [Equipment::factory()->underMaintenance()->create()->id],
                'room_id' => Room::factory()->inactive()->create()->id,
            ]))
            ->assertSessionHasErrors(['equipment_ids.0', 'room_id']);
    }

    // ================= FR-R2: conflict check =================

    public function test_overlapping_reservation_is_rejected_and_names_the_item_and_time(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0001', 'name' => 'Epson Projector']);
        $existing = $this->existingReservation($item, $this->start, $this->start->copy()->addHours(2));

        // New request 2:00–4:00 PM overlaps the existing 1:00–3:00 PM.
        $response = $this->actingAs(User::factory()->create())->post(route('reservations.store'), $this->payload([
            'equipment_ids' => [$item->id],
            'start_at' => $this->start->copy()->addHour()->format('Y-m-d\TH:i'),
            'end_at' => $this->start->copy()->addHours(3)->format('Y-m-d\TH:i'),
        ]));

        $response->assertSessionHasErrors('equipment_ids');
        $message = session('errors')->first('equipment_ids');
        $this->assertStringContainsString('PRJ-0001 Epson Projector is already reserved', $message);
        $this->assertStringContainsString($this->start->format('g:i A'), $message);
        $this->assertStringContainsString($existing->reference_no, $message);
        $this->assertDatabaseCount('reservations', 1); // nothing new was saved
    }

    public function test_touching_times_are_allowed(): void
    {
        $item = Equipment::factory()->create();
        $this->existingReservation($item, $this->start, $this->start->copy()->addHours(2)); // 1:00–3:00 PM

        // 3:00–5:00 PM starts exactly when the other one ends: not a conflict.
        $this->actingAs(User::factory()->create())->post(route('reservations.store'), $this->payload([
            'equipment_ids' => [$item->id],
            'start_at' => $this->start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'end_at' => $this->start->copy()->addHours(4)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_pending_and_released_reservations_also_block(): void
    {
        $user = User::factory()->create();
        foreach (['pending', 'released'] as $status) {
            $item = Equipment::factory()->create();
            $this->existingReservation($item, $this->start, $this->start->copy()->addHours(2), $status);

            $this->actingAs($user)
                ->post(route('reservations.store'), $this->payload(['equipment_ids' => [$item->id]]))
                ->assertSessionHasErrors('equipment_ids');
        }
    }

    public function test_cancelled_rejected_and_returned_reservations_do_not_block(): void
    {
        $item = Equipment::factory()->create();
        foreach (['cancelled', 'rejected', 'returned'] as $status) {
            $this->existingReservation($item, $this->start, $this->start->copy()->addHours(2), $status);
        }

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload(['equipment_ids' => [$item->id]]))
            ->assertSessionHasNoErrors();
    }

    public function test_room_conflict_is_rejected(): void
    {
        $room = Room::factory()->create(['name' => 'Computer Lab 2']);
        Reservation::factory()->for($room)->at($this->start, $this->start->copy()->addHours(2))->approved()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload(['room_id' => $room->id]))
            ->assertSessionHasErrors('room_id');

        $this->assertStringContainsString('Computer Lab 2 is already reserved', session('errors')->first('room_id'));
    }

    // ================= FR-R3: borrowing rules =================

    public function test_start_must_be_at_least_60_minutes_ahead(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload([
                'room_id' => Room::factory()->create()->id,
                'start_at' => now()->addMinutes(30)->format('Y-m-d\TH:i'),
                'end_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors(['start_at' => 'Reservations must start at least 60 minutes from now.']);
    }

    public function test_start_cannot_be_more_than_14_days_ahead(): void
    {
        $start = now()->addDays(15)->setTime(9, 0);

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload([
                'room_id' => Room::factory()->create()->id,
                'start_at' => $start->format('Y-m-d\TH:i'),
                'end_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors(['start_at' => 'You can reserve at most 14 days ahead.']);
    }

    public function test_end_must_be_after_start(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $this->payload([
                'room_id' => Room::factory()->create()->id,
                'end_at' => $this->start->copy()->subHour()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('end_at');
    }

    public function test_duration_limit_depends_on_the_role(): void
    {
        $room = Room::factory()->create();
        $tenHours = $this->payload([
            'room_id' => $room->id,
            'start_at' => $this->start->copy()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'end_at' => $this->start->copy()->setTime(18, 0)->format('Y-m-d\TH:i'),
        ]);

        // Students: max 8 hours.
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), $tenHours)
            ->assertSessionHasErrors(['end_at' => 'Student reservations can be at most 8 hours long.']);

        // Faculty: max 72 hours, so 10 hours is fine.
        $this->actingAs(User::factory()->faculty()->create())
            ->post(route('reservations.store'), $tenHours)
            ->assertSessionHasNoErrors();
    }

    public function test_user_with_an_overdue_reservation_cannot_reserve(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->overdue()->create();

        $this->actingAs($student)
            ->post(route('reservations.store'), $this->payload(['room_id' => Room::factory()->create()->id]))
            ->assertSessionHasErrors('reservation');

        $this->assertStringContainsString('overdue', session('errors')->first('reservation'));
        $this->assertSame(1, $student->reservations()->count());
    }

    public function test_student_cannot_exceed_3_active_reservations(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->count(3)->sequence(
            fn ($seq) => ['start_at' => now()->addDays($seq->index + 2), 'end_at' => now()->addDays($seq->index + 2)->addHour()]
        )->create();

        $this->actingAs($student)
            ->post(route('reservations.store'), $this->payload(['room_id' => Room::factory()->create()->id]))
            ->assertSessionHasErrors('reservation');

        $this->assertSame(3, $student->reservations()->count());
    }

    public function test_faculty_has_no_active_reservation_limit(): void
    {
        $faculty = User::factory()->faculty()->create();
        Reservation::factory()->for($faculty)->count(3)->create(['start_at' => now()->addDays(5), 'end_at' => now()->addDays(5)->addHour()]);

        $this->actingAs($faculty)
            ->post(route('reservations.store'), $this->payload(['room_id' => Room::factory()->create()->id]))
            ->assertSessionHasNoErrors();
    }

    // ================= FR-R9: view and edit =================

    public function test_borrower_can_edit_own_pending_reservation_without_conflicting_with_itself(): void
    {
        $student = User::factory()->create();
        $item = Equipment::factory()->create();
        $reservation = Reservation::factory()->for($student)->at($this->start, $this->start->copy()->addHours(2))->create();
        $reservation->equipment()->attach($item);

        // Same item, slightly later and overlapping its own old time: must NOT count as a conflict.
        $this->actingAs($student)
            ->put(route('reservations.update', $reservation), $this->payload([
                'purpose' => 'Updated purpose',
                'equipment_ids' => [$item->id],
                'start_at' => $this->start->copy()->addHour()->format('Y-m-d\TH:i'),
                'end_at' => $this->start->copy()->addHours(3)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated purpose', $reservation->fresh()->purpose);
    }

    public function test_edit_still_checks_conflicts_with_other_reservations(): void
    {
        $student = User::factory()->create();
        $item = Equipment::factory()->create();
        $this->existingReservation($item, $this->start->copy()->addHours(3), $this->start->copy()->addHours(5)); // someone else, 4–6 PM
        $mine = Reservation::factory()->for($student)->at($this->start, $this->start->copy()->addHours(2))->create();

        $this->actingAs($student)
            ->put(route('reservations.update', $mine), $this->payload([
                'equipment_ids' => [$item->id],
                'end_at' => $this->start->copy()->addHours(4)->format('Y-m-d\TH:i'), // now 1–5 PM: overlaps
            ]))
            ->assertSessionHasErrors('equipment_ids');
    }

    public function test_approved_reservation_cannot_be_edited(): void
    {
        $student = User::factory()->create();
        $reservation = Reservation::factory()->for($student)->approved()->create();

        $this->actingAs($student)->get(route('reservations.edit', $reservation))->assertForbidden();
        $this->actingAs($student)->put(route('reservations.update', $reservation), $this->payload())->assertForbidden();
    }

    public function test_users_cannot_see_or_edit_other_peoples_reservations_but_admin_can_see(): void
    {
        $reservation = Reservation::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('reservations.show', $reservation))->assertForbidden();
        $this->actingAs($other)->get(route('reservations.edit', $reservation))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee($reservation->reference_no);
    }

    public function test_create_form_preselects_an_item_from_its_page(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0007']);

        $this->actingAs(User::factory()->create())
            ->get(route('reservations.create', ['equipment' => $item->id]))
            ->assertOk()
            ->assertSee('PRJ-0007')
            ->assertSee('value="' . $item->id . '" checked', false);
    }
}
