<?php

namespace Tests\Feature;

use App\Enums\EquipmentCondition;
use App\Enums\ReservationStatus;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Status flow (FR-R4), release (FR-R5), return (FR-R6), overdue (FR-R7).
 */
class ReservationStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function reservationWithItem(string $state = 'pending', array $attributes = []): Reservation
    {
        $factory = Reservation::factory();
        if ($state !== 'pending') {
            $factory = $factory->{$state}();
        }
        $reservation = $factory->create($attributes);
        $reservation->equipment()->attach(Equipment::factory()->create(), $state === 'released' ? ['condition_out' => 'good'] : []);

        return $reservation->fresh();
    }

    // ================= the full happy path =================

    public function test_full_flow_pending_approved_released_returned(): void
    {
        $reservation = $this->reservationWithItem();
        $item = $reservation->equipment->first();

        // Approve
        $this->actingAs($this->admin)->patch(route('admin.reservations.approve', $reservation))->assertSessionHas('success');
        $reservation->refresh();
        $this->assertSame(ReservationStatus::Approved, $reservation->status);
        $this->assertTrue($reservation->approver->is($this->admin));
        $this->assertNotNull($reservation->approved_at);

        // Release with condition_out
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.release', $reservation), ['conditions' => [$item->id => 'fair']])
            ->assertSessionHas('success');
        $reservation->refresh();
        $this->assertSame(ReservationStatus::Released, $reservation->status);
        $this->assertNotNull($reservation->released_at);
        $this->assertSame('fair', $reservation->equipment->first()->pivot->condition_out);

        // Receive with condition_in + remarks
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.receive', $reservation), ['conditions' => [$item->id => 'poor'], 'remarks' => 'Scratched case'])
            ->assertSessionHas('success');
        $reservation->refresh();
        $this->assertSame(ReservationStatus::Returned, $reservation->status);
        $this->assertNotNull($reservation->returned_at);
        $this->assertSame('Scratched case', $reservation->remarks);
        $this->assertSame('poor', $reservation->equipment->first()->pivot->condition_in);
        $this->assertSame(EquipmentCondition::Poor, $item->fresh()->condition); // inventory updated
    }

    // ================= invalid transitions are refused =================

    public function test_pending_cannot_jump_to_released_or_returned(): void
    {
        $reservation = $this->reservationWithItem();
        $itemId = $reservation->equipment->first()->id;

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.receive', $reservation), ['conditions' => [$itemId => 'good']])
            ->assertSessionHasErrors(['status' => 'A Pending reservation cannot be changed to Returned.']);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.release', $reservation), ['conditions' => [$itemId => 'good']])
            ->assertSessionHasErrors('status');

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_final_statuses_cannot_change(): void
    {
        foreach (['returned', 'cancelled', 'rejected'] as $final) {
            $reservation = Reservation::factory()->create(['status' => $final]);

            $this->actingAs($this->admin)
                ->patch(route('admin.reservations.approve', $reservation))
                ->assertSessionHasErrors('status');

            $this->assertSame($final, $reservation->fresh()->status->value);
        }
    }

    public function test_service_refuses_every_transition_not_in_the_flow(): void
    {
        $service = app(ReservationService::class);
        $refused = 0;

        foreach (ReservationStatus::cases() as $from) {
            foreach (ReservationStatus::cases() as $to) {
                if ($from->canTransitionTo($to)) {
                    continue;
                }
                $reservation = Reservation::factory()->create(['status' => $from]);
                try {
                    $service->transition($reservation, $to, $this->admin);
                    $this->fail("{$from->value} -> {$to->value} should have been refused.");
                } catch (ValidationException) {
                    $refused++;
                }
                $this->assertSame($from, $reservation->fresh()->status);
            }
        }

        $this->assertSame(36 - 6, $refused); // 6 statuses x 6 = 36 pairs, only 6 are allowed
    }

    // ================= who can do what =================

    public function test_borrower_can_cancel_own_pending_or_approved_reservation(): void
    {
        foreach (['pending', 'approved'] as $state) {
            $reservation = $this->reservationWithItem($state);

            $this->actingAs($reservation->user)
                ->patch(route('reservations.cancel', $reservation))
                ->assertSessionHas('success');

            $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        }
    }

    public function test_borrower_cannot_cancel_a_released_reservation(): void
    {
        $reservation = $this->reservationWithItem('released');

        $this->actingAs($reservation->user)
            ->patch(route('reservations.cancel', $reservation))
            ->assertSessionHasErrors('status');

        $this->assertSame(ReservationStatus::Released, $reservation->fresh()->status);
    }

    public function test_users_cannot_cancel_other_peoples_reservations_but_admin_can(): void
    {
        $reservation = $this->reservationWithItem();

        $this->actingAs(User::factory()->create())
            ->patch(route('reservations.cancel', $reservation))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('reservations.cancel', $reservation))
            ->assertSessionHas('success');
    }

    public function test_only_admin_can_approve_reject_release_and_receive(): void
    {
        $reservation = $this->reservationWithItem();
        $borrower = $reservation->user;

        foreach (['approve', 'reject', 'release', 'receive'] as $action) {
            $this->actingAs($borrower)->patch(route("admin.reservations.{$action}", $reservation))->assertForbidden();
        }
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    // ================= reject, approve re-check, release/receive details =================

    public function test_reject_requires_a_reason_and_saves_it(): void
    {
        $reservation = $this->reservationWithItem();

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.reject', $reservation), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.reject', $reservation), ['rejection_reason' => 'Needed for an event'])
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Rejected, $reservation->status);
        $this->assertSame('Needed for an event', $reservation->rejection_reason);
    }

    public function test_approve_runs_the_conflict_check_again(): void
    {
        // A pending request that overlaps an approved one for the same item. The form would have
        // refused it, but it can exist e.g. in old data, so approval must check again (FR-R2).
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0001']);
        $approved = Reservation::factory()->approved()->create(); // tomorrow 1–3 PM
        $pending = Reservation::factory()->create();              // same time
        $approved->equipment()->attach($item);
        $pending->equipment()->attach($item);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.approve', $pending))
            ->assertSessionHasErrors('equipment_ids');

        $this->assertStringContainsString($approved->reference_no, session('errors')->first('equipment_ids'));
        $this->assertSame(ReservationStatus::Pending, $pending->fresh()->status);
    }

    public function test_approve_succeeds_when_there_is_no_conflict(): void
    {
        $reservation = $this->reservationWithItem();

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.approve', $reservation))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
    }

    public function test_item_under_maintenance_cannot_be_approved_or_released(): void
    {
        $reservation = $this->reservationWithItem('approved');
        $item = $reservation->equipment->first();
        $item->update(['status' => 'under_maintenance']);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.release', $reservation), ['conditions' => [$item->id => 'good']])
            ->assertSessionHasErrors('status');

        $this->assertStringContainsString('under maintenance', session('errors')->first('status'));
        $this->assertSame(ReservationStatus::Approved, $reservation->fresh()->status);
    }

    public function test_release_needs_a_condition_for_every_item_and_no_damaged(): void
    {
        $reservation = $this->reservationWithItem('approved');
        $second = Equipment::factory()->create();
        $reservation->equipment()->attach($second);
        $first = $reservation->equipment->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.release', $reservation), ['conditions' => [$first->id => 'damaged']])
            ->assertSessionHasErrors(["conditions.{$first->id}", "conditions.{$second->id}"]);

        $this->assertSame(ReservationStatus::Approved, $reservation->fresh()->status);
    }

    public function test_receiving_a_damaged_item_asks_for_a_damage_report(): void
    {
        $reservation = $this->reservationWithItem('released');
        $item = $reservation->equipment->first();

        // FR-R6: a damaged return opens the damage report form, pre-filled for that item and reservation.
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.receive', $reservation), ['conditions' => [$item->id => 'damaged']])
            ->assertRedirect(route('damage-reports.create', ['equipment' => $item->id, 'reservation' => $reservation->id]))
            ->assertSessionHas('error');

        $this->assertSame('damaged', $reservation->fresh()->equipment->first()->pivot->condition_in);
        $this->assertSame(EquipmentCondition::Good, $item->fresh()->condition); // left for the damage report
    }

    public function test_returned_item_is_free_again(): void
    {
        $reservation = $this->reservationWithItem('released');
        $item = $reservation->equipment->first();
        $this->assertSame('borrowed', $item->currentAvailability()['state']);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.receive', $reservation), ['conditions' => [$item->id => 'good']]);

        $this->assertSame('available', $item->fresh()->currentAvailability()['state']);
    }

    // ================= FR-R7: overdue =================

    public function test_overdue_is_computed_and_shown(): void
    {
        $reservation = Reservation::factory()->overdue()->create();

        $this->assertTrue($reservation->isOverdue());
        $this->assertSame(1, Reservation::overdue()->count());

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', $reservation))
            ->assertSee('Overdue')
            ->assertSee('was due back on');
    }

    public function test_action_buttons_follow_role_and_status(): void
    {
        $pending = $this->reservationWithItem();

        $this->actingAs($this->admin)->get(route('reservations.show', $pending))
            ->assertSee(route('admin.reservations.approve', $pending))
            ->assertSee(route('admin.reservations.reject', $pending))
            ->assertDontSee('Release (hand over)');

        $this->actingAs($pending->user)->get(route('reservations.show', $pending))
            ->assertSee('Cancel reservation')
            ->assertSee('You can still edit it while it is pending.')
            ->assertDontSee(route('admin.reservations.approve', $pending));

        $released = $this->reservationWithItem('released');
        $this->actingAs($this->admin)->get(route('reservations.show', $released))
            ->assertSee('Receive (return)')->assertDontSee('Cancel reservation');
    }
}
