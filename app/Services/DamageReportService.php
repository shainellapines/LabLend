<?php

namespace App\Services;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Damage report rules (FR-D1–D3), kept in one place like ReservationService.
 */
class DamageReportService
{
    /**
     * FR-D1 + FR-D3: save the report and change the item's status automatically:
     *   major -> under maintenance, lost -> retired, minor -> no change.
     */
    public function create(User $reporter, array $data, ?string $photoPath): DamageReport
    {
        return DB::transaction(function () use ($reporter, $data, $photoPath) {
            $report = new DamageReport($data);
            $report->photo_path = $photoPath;
            $report->reporter()->associate($reporter);
            $report->status = DamageStatus::Open;
            $report->save();

            $newStatus = match ($report->severity) {
                DamageSeverity::Major => EquipmentStatus::UnderMaintenance,
                DamageSeverity::Lost => EquipmentStatus::Retired,
                DamageSeverity::Minor => null,
            };
            if ($newStatus) {
                $report->equipment->update(['status' => $newStatus]);
            }

            return $report;
        });
    }

    /**
     * FR-D2: admin moves the report forward (open -> under repair -> resolved) and adds notes.
     * FR-D3: when resolving, the admin may put the item back into circulation ($makeAvailable).
     */
    public function update(DamageReport $report, array $data, bool $makeAvailable): DamageReport
    {
        return DB::transaction(function () use ($report, $data, $makeAvailable) {
            $status = DamageStatus::from($data['status']);

            $report->status = $status;
            $report->resolution_notes = $data['resolution_notes'] ?? null;
            $report->resolved_at = $status === DamageStatus::Resolved ? ($report->resolved_at ?? now()) : null;
            $report->save();

            if ($status === DamageStatus::Resolved && $makeAvailable && ! $report->equipment->trashed()) {
                $report->equipment->update(['status' => EquipmentStatus::Available]);
            }

            return $report;
        });
    }

    /**
     * FR-R6 helper: after a return with several damaged items, find the next one that still has no report,
     * so the admin is taken from one pre-filled form to the next.
     */
    public function nextUnreportedDamagedItem(Reservation $reservation): ?Equipment
    {
        $reported = $reservation->damageReports()->pluck('equipment_id')->all();

        return $reservation->equipment()
            ->wherePivot('condition_in', EquipmentCondition::Damaged->value)
            ->whereNotIn('equipment.id', $reported)
            ->first();
    }
}
