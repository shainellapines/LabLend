<?php

namespace Tests\Feature;

use App\Enums\DamageStatus;
use App\Enums\EquipmentStatus;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Damage reports (FR-D1–D3) and the "damaged on return" flow (FR-R6).
 */
class DamageReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(User $user, Equipment $item, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('damage-reports.store'), array_merge([
            'equipment_id' => $item->id,
            'severity' => 'minor',
            'description' => 'Scratched case',
        ], $overrides));
    }

    // ================= FR-D1 + FR-D3: filing =================

    public function test_minor_report_does_not_change_the_item_status(): void
    {
        $item = Equipment::factory()->create();

        $this->report(User::factory()->create(), $item)->assertSessionHasNoErrors();

        $this->assertSame(DamageStatus::Open, DamageReport::first()->status);
        $this->assertSame(EquipmentStatus::Available, $item->fresh()->status);
    }

    public function test_major_report_puts_the_item_under_maintenance(): void
    {
        $item = Equipment::factory()->create();

        $this->report(User::factory()->create(), $item, ['severity' => 'major']);

        $this->assertSame(EquipmentStatus::UnderMaintenance, $item->fresh()->status);
    }

    public function test_lost_report_retires_the_item(): void
    {
        $item = Equipment::factory()->create();

        $this->report(User::factory()->create(), $item, ['severity' => 'lost']);

        $this->assertSame(EquipmentStatus::Retired, $item->fresh()->status);
    }

    public function test_photo_is_stored_and_validated(): void
    {
        Storage::fake('public');
        $item = Equipment::factory()->create();
        $user = User::factory()->create();

        $this->report($user, $item, ['photo' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        $this->report($user, $item, ['photo' => UploadedFile::fake()->image('crack.jpg')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists(DamageReport::first()->photo_path);
    }

    public function test_description_and_valid_severity_are_required(): void
    {
        $this->report(User::factory()->create(), Equipment::factory()->create(), ['description' => '', 'severity' => 'broken'])
            ->assertSessionHasErrors(['description', 'severity']);
    }

    public function test_borrower_can_only_link_their_own_reservation_and_it_must_include_the_item(): void
    {
        $student = User::factory()->create();
        $item = Equipment::factory()->create();
        $someoneElses = Reservation::factory()->returned()->create();
        $someoneElses->equipment()->attach($item);
        $mineWithoutItem = Reservation::factory()->for($student)->returned()->create();

        $this->report($student, $item, ['reservation_id' => $someoneElses->id])
            ->assertSessionHasErrors(['reservation_id' => 'You can only link your own reservations.']);

        $this->report($student, $item, ['reservation_id' => $mineWithoutItem->id])
            ->assertSessionHasErrors(['reservation_id' => 'That reservation does not include the selected item.']);
    }

    // ================= FR-R6: damaged on return =================

    public function test_damaged_return_leads_to_prefilled_forms_one_item_after_another(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->released()->create(['reference_no' => 'RSV-2026-0300']);
        [$first, $second] = Equipment::factory()->count(2)->create()->all();
        $reservation->equipment()->attach([$first->id => ['condition_out' => 'good'], $second->id => ['condition_out' => 'good']]);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.receive', $reservation), ['conditions' => [$first->id => 'damaged', $second->id => 'damaged']])
            ->assertRedirect(route('damage-reports.create', ['equipment' => $first->id, 'reservation' => $reservation->id]));

        // The form is pre-filled with the item and shows the linked reservation.
        $this->actingAs($admin)
            ->get(route('damage-reports.create', ['equipment' => $first->id, 'reservation' => $reservation->id]))
            ->assertOk()
            ->assertSee('RSV-2026-0300')
            ->assertSee('value="' . $first->id . '" selected', false);

        // After the first report, the admin goes straight to the second damaged item.
        $this->report($admin, $first, ['reservation_id' => $reservation->id, 'severity' => 'major'])
            ->assertRedirect(route('damage-reports.create', ['equipment' => $second->id, 'reservation' => $reservation->id]));

        // After the last one, to the report page.
        $this->report($admin, $second, ['reservation_id' => $reservation->id])
            ->assertRedirect(route('damage-reports.show', DamageReport::latest('id')->first()));
    }

    public function test_someone_elses_reservation_is_not_prefilled(): void
    {
        $reservation = Reservation::factory()->returned()->create(['reference_no' => 'RSV-2026-0400']);

        $this->actingAs(User::factory()->create())
            ->get(route('damage-reports.create', ['reservation' => $reservation->id]))
            ->assertOk()
            ->assertDontSee('RSV-2026-0400');
    }

    // ================= FR-D2: list, view, admin status updates =================

    public function test_borrowers_see_only_their_own_reports_and_admin_sees_all(): void
    {
        $student = User::factory()->create();
        DamageReport::factory()->for($student, 'reporter')->create(['description' => 'My report']);
        DamageReport::factory()->create(['description' => 'Other report']);

        $this->actingAs($student)->get(route('damage-reports.index'))
            ->assertSee('My report')->assertDontSee('Other report');

        $this->actingAs(User::factory()->admin()->create())->get(route('damage-reports.index'))
            ->assertSee('My report')->assertSee('Other report');
    }

    public function test_borrower_cannot_view_someone_elses_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('damage-reports.show', DamageReport::factory()->create()))
            ->assertForbidden();
    }

    public function test_admin_moves_report_forward_and_can_make_the_item_available_again(): void
    {
        $admin = User::factory()->admin()->create();
        $item = Equipment::factory()->underMaintenance()->create();
        $report = DamageReport::factory()->for($item)->create(['severity' => 'major']);

        $this->actingAs($admin)->patch(route('admin.damage-reports.update', $report), ['status' => 'under_repair', 'resolution_notes' => 'Sent to supplier'])
            ->assertSessionHasNoErrors();
        $this->assertSame(DamageStatus::UnderRepair, $report->fresh()->status);
        $this->assertNull($report->fresh()->resolved_at);

        $this->actingAs($admin)->patch(route('admin.damage-reports.update', $report), [
            'status' => 'resolved', 'resolution_notes' => 'Lens replaced', 'make_available' => '1',
        ])->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(DamageStatus::Resolved, $report->status);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame(EquipmentStatus::Available, $item->fresh()->status);
    }

    public function test_resolving_without_the_checkbox_keeps_the_item_out_of_circulation(): void
    {
        $item = Equipment::factory()->retired()->create();
        $report = DamageReport::factory()->for($item)->create(['severity' => 'lost']);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.damage-reports.update', $report), ['status' => 'resolved', 'resolution_notes' => 'Written off']);

        $this->assertSame(EquipmentStatus::Retired, $item->fresh()->status);
    }

    public function test_resolved_needs_notes_and_reports_cannot_move_backwards(): void
    {
        $admin = User::factory()->admin()->create();
        $report = DamageReport::factory()->create();

        $this->actingAs($admin)->patch(route('admin.damage-reports.update', $report), ['status' => 'resolved'])
            ->assertSessionHasErrors('resolution_notes');

        // forceFill: status is not mass-assignable (only DamageReportService sets it), so update() would ignore it.
        $report->forceFill(['status' => DamageStatus::Resolved, 'resolved_at' => now()])->save();

        $this->actingAs($admin)->patch(route('admin.damage-reports.update', $report), ['status' => 'open'])
            ->assertSessionHasErrors('status');
        $this->assertSame(DamageStatus::Resolved, $report->fresh()->status);
    }

    public function test_only_admin_can_update_or_delete_and_delete_removes_the_photo(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('crack.jpg')->store('damage-reports', 'public');
        $student = User::factory()->create();
        $report = DamageReport::factory()->for($student, 'reporter')->create(['photo_path' => $path]);

        $this->actingAs($student)->patch(route('admin.damage-reports.update', $report), ['status' => 'resolved'])->assertForbidden();
        $this->actingAs($student)->delete(route('admin.damage-reports.destroy', $report))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.damage-reports.destroy', $report))
            ->assertRedirect(route('damage-reports.index'));

        $this->assertModelMissing($report);
        Storage::disk('public')->assertMissing($path);
    }
}
