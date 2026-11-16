<?php

namespace Tests\Feature;

use App\Enums\EquipmentStatus;
use App\Enums\ReservationStatus;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 1.5: relationships, scopes, enums, and the demo seed data.
 */
class ModelsAndSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_relationships_and_pivot_conditions(): void
    {
        $room = Room::factory()->create();
        $item = Equipment::factory()->create();
        $reservation = Reservation::factory()->for($room)->create();
        $reservation->equipment()->attach($item, ['condition_out' => 'good']);

        $reservation->refresh();
        $this->assertTrue($reservation->room->is($room));
        $this->assertTrue($reservation->equipment->first()->is($item));
        $this->assertSame('good', $reservation->equipment->first()->pivot->condition_out);
        $this->assertCount(1, $item->reservations);
        $this->assertCount(1, $reservation->user->reservations);
    }

    public function test_soft_deleted_equipment_still_shows_in_old_reservations_and_reports(): void
    {
        $item = Equipment::factory()->create();
        $reservation = Reservation::factory()->returned()->create();
        $reservation->equipment()->attach($item);
        $report = DamageReport::factory()->for($item)->create();

        $item->delete(); // soft delete

        $this->assertSoftDeleted($item);
        $this->assertCount(1, $reservation->fresh()->equipment);
        $this->assertNotNull($report->fresh()->equipment);
        $this->assertSame(0, Equipment::count()); // normal queries hide it
    }

    public function test_overlapping_scope_allows_touching_times(): void
    {
        $day = now()->addDay();
        Reservation::factory()->at($day->copy()->setTime(13, 0), $day->copy()->setTime(15, 0))->create();

        // 2:00–4:00 PM overlaps 1:00–3:00 PM.
        $this->assertSame(1, Reservation::overlapping($day->copy()->setTime(14, 0), $day->copy()->setTime(16, 0))->count());
        // 3:00–5:00 PM only touches it: allowed.
        $this->assertSame(0, Reservation::overlapping($day->copy()->setTime(15, 0), $day->copy()->setTime(17, 0))->count());
        // 11:00 AM–1:00 PM touches the start: allowed.
        $this->assertSame(0, Reservation::overlapping($day->copy()->setTime(11, 0), $day->copy()->setTime(13, 0))->count());
    }

    public function test_active_and_overdue_scopes(): void
    {
        Reservation::factory()->create();               // pending  -> active
        Reservation::factory()->approved()->create();   // approved -> active
        $overdue = Reservation::factory()->overdue()->create(); // released, ended yesterday
        Reservation::factory()->returned()->create();   // not active

        $this->assertSame(3, Reservation::active()->count());
        $this->assertSame(1, Reservation::overdue()->count());
        $this->assertTrue($overdue->isOverdue());
    }

    public function test_status_transitions_follow_the_flow(): void
    {
        $this->assertTrue(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Approved));
        $this->assertTrue(ReservationStatus::Approved->canTransitionTo(ReservationStatus::Released));
        $this->assertFalse(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Returned));
        $this->assertFalse(ReservationStatus::Returned->canTransitionTo(ReservationStatus::Pending));
    }

    public function test_demo_seed_data_matches_the_demo_scenario(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, User::count());                      // 1 admin, 2 faculty, 5 students
        $this->assertGreaterThanOrEqual(25, Equipment::count());
        $this->assertSame(4, Room::count());
        $this->assertGreaterThanOrEqual(80, Reservation::count());
        $this->assertSame(1, Reservation::overdue()->count());    // exactly one overdue item
        $this->assertSame(1, DamageReport::count());
        $this->assertSame(EquipmentStatus::UnderMaintenance, DamageReport::first()->equipment->status);

        // student@lablend.test is not the overdue borrower (they make a new reservation in the demo).
        $overdueBorrower = Reservation::overdue()->first()->user;
        $this->assertNotSame('student@lablend.test', $overdueBorrower->email);
    }

    public function test_seed_data_has_no_double_bookings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $reservations = Reservation::active()->with('equipment')->get();

        foreach ($reservations as $reservation) {
            $clashes = Reservation::active()
                ->whereKeyNot($reservation->id)
                ->overlapping($reservation->start_at, $reservation->end_at)
                ->where(function ($q) use ($reservation) {
                    $q->whereHas('equipment', fn ($e) => $e->whereIn('equipment.id', $reservation->equipment->pluck('id')));
                    if ($reservation->room_id) {
                        $q->orWhere('room_id', $reservation->room_id);
                    }
                })
                ->count();

            $this->assertSame(0, $clashes, "Reservation {$reservation->reference_no} is double-booked.");
        }
    }
}
