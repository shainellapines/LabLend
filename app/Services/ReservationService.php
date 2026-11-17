<?php

namespace App\Services;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All reservation business rules live here, so they are the same no matter where a reservation
 * is created or changed (form, admin approval in step 2.5, availability check in step 3.4).
 *
 * Split of responsibilities:
 *  - ReservationRequest (Form Request): is the input well-formed? time window, duration, items exist.
 *  - ReservationService (this class): rules that depend on OTHER reservations: overdue block,
 *    student limit, and the conflict (double-booking) check.
 *
 * Errors are thrown as ValidationException, so they appear under the form fields like normal validation errors.
 */
class ReservationService
{
    /** FR-R1: create a pending reservation for $user. $data comes from ReservationRequest::validated(). */
    public function create(User $user, array $data): Reservation
    {
        $this->ensureUserCanBorrow($user);

        // A transaction: either the reservation AND its item rows are all saved, or nothing is.
        // Throwing an exception inside it (e.g. a conflict) automatically undoes everything.
        return DB::transaction(function () use ($user, $data) {
            [$start, $end, $itemIds, $roomId] = $this->extract($data);

            $this->lockResources($itemIds, $roomId);
            $this->ensureNoConflicts($start, $end, $itemIds, $roomId);

            $reservation = new Reservation([
                'purpose' => $data['purpose'],
                'start_at' => $start,
                'end_at' => $end,
                'room_id' => $roomId,
            ]);
            $reservation->user()->associate($user);
            $reservation->status = ReservationStatus::Pending;
            $reservation->reference_no = 'TMP-' . Str::uuid(); // replaced right below, once we have the id
            $reservation->save();

            $reservation->reference_no = $this->referenceNumberFor($reservation);
            $reservation->save();

            $reservation->equipment()->sync($itemIds);

            return $reservation;
        });
    }

    /** FR-R9: the borrower edits a pending reservation. Same conflict check, ignoring the reservation itself. */
    public function update(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data) {
            [$start, $end, $itemIds, $roomId] = $this->extract($data);

            $this->lockResources($itemIds, $roomId);
            $this->ensureNoConflicts($start, $end, $itemIds, $roomId, ignoreId: $reservation->id);

            $reservation->update([
                'purpose' => $data['purpose'],
                'start_at' => $start,
                'end_at' => $end,
                'room_id' => $roomId,
            ]);
            $reservation->equipment()->sync($itemIds);

            return $reservation;
        });
    }

    /**
     * FR-R4: the ONLY way a reservation's status changes. Every action (cancel, approve, reject,
     * release, receive) calls this method, so the status flow is enforced in one place:
     *
     *   pending  -> approved | rejected | cancelled
     *   approved -> released | cancelled
     *   released -> returned
     *
     * $data holds the extra input some steps need:
     *   rejected: ['rejection_reason' => '...']
     *   released: ['conditions' => [equipment_id => 'good'|'fair'|'poor']]
     *   returned: ['conditions' => [equipment_id => 'good'|'fair'|'poor'|'damaged'], 'remarks' => '...']
     */
    public function transition(Reservation $reservation, ReservationStatus $to, User $actor, array $data = []): Reservation
    {
        $from = $reservation->status;

        // The allowed moves are defined in the ReservationStatus enum (allowedTransitions()).
        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "A {$from->label()} reservation cannot be changed to {$to->label()}.",
            ]);
        }

        return DB::transaction(function () use ($reservation, $to, $actor, $data) {
            match ($to) {
                ReservationStatus::Approved => $this->applyApproval($reservation, $actor),
                ReservationStatus::Rejected => $this->applyRejection($reservation, $actor, $data),
                ReservationStatus::Released => $this->applyRelease($reservation, $data),
                ReservationStatus::Returned => $this->applyReturn($reservation, $data),
                ReservationStatus::Cancelled, ReservationStatus::Pending => null, // nothing extra to record
            };

            $reservation->status = $to;
            $reservation->save();

            return $reservation;
        });
    }

    /**
     * FR-R2: find active reservations that overlap the time range and use any of the same items or room.
     * Returns a list of clashes: [['type' => 'equipment'|'room', 'id' => 5, 'name' => 'PRJ-0001 Epson Projector',
     *                              'reservation' => Reservation], ...]
     * Public because approval (2.5) and the availability endpoint (3.4) reuse it.
     */
    public function findConflicts(Carbon $start, Carbon $end, array $itemIds, ?int $roomId, ?int $ignoreId = null): array
    {
        $conflicts = [];

        // The overlap rule itself is in Reservation::scopeOverlapping(): existing.start < new.end AND existing.end > new.start
        $base = fn () => Reservation::active()
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->orderBy('start_at');

        if ($itemIds) {
            $clashing = $base()
                ->whereHas('equipment', fn ($q) => $q->whereIn('equipment.id', $itemIds))
                ->with(['equipment' => fn ($q) => $q->whereIn('equipment.id', $itemIds)]) // only the items that clash
                ->get();

            foreach ($clashing as $reservation) {
                foreach ($reservation->equipment as $item) {
                    $conflicts[] = ['type' => 'equipment', 'id' => $item->id, 'name' => "{$item->asset_tag} {$item->name}", 'reservation' => $reservation];
                }
            }
        }

        if ($roomId) {
            foreach ($base()->where('room_id', $roomId)->with('room')->get() as $reservation) {
                $conflicts[] = ['type' => 'room', 'id' => $roomId, 'name' => $reservation->room->name, 'reservation' => $reservation];
            }
        }

        return $conflicts;
    }

    // ---------- rules ----------

    /** FR-R3: users with an overdue item, and students at their limit, cannot create new reservations. */
    public function ensureUserCanBorrow(User $user): void
    {
        if ($user->reservations()->overdue()->exists()) {
            throw ValidationException::withMessages([
                'reservation' => 'You have an overdue item. Please return it before making a new reservation.',
            ]);
        }

        $limit = config('lablend.student_max_active');
        if ($user->role === UserRole::Student && $user->reservations()->active()->count() >= $limit) {
            throw ValidationException::withMessages([
                'reservation' => "Students can have at most {$limit} active reservations (pending, approved, or borrowed). Cancel or finish one first.",
            ]);
        }
    }

    private function ensureNoConflicts(Carbon $start, Carbon $end, array $itemIds, ?int $roomId, ?int $ignoreId = null): void
    {
        $conflicts = $this->findConflicts($start, $end, $itemIds, $roomId, $ignoreId);
        if ($conflicts === []) {
            return;
        }

        $messages = [];
        foreach ($conflicts as $conflict) {
            $r = $conflict['reservation'];
            $field = $conflict['type'] === 'room' ? 'room_id' : 'equipment_ids';
            $messages[$field][] = sprintf('%s is already reserved %s – %s (%s).',
                $conflict['name'],
                $r->start_at->format('M d, Y g:i A'),
                $r->end_at->format($r->end_at->isSameDay($r->start_at) ? 'g:i A' : 'M d, Y g:i A'),
                $r->reference_no);
        }

        throw ValidationException::withMessages($messages);
    }

    // ---------- status-change steps (called only from transition()) ----------

    /** Approving runs the conflict check again (FR-R2): something may have been approved since the request. */
    private function applyApproval(Reservation $reservation, User $admin): void
    {
        $itemIds = $reservation->equipment()->pluck('equipment.id')->all();

        $this->lockResources($itemIds, $reservation->room_id);
        $this->ensureItemsStillUsable($reservation);
        $this->ensureNoConflicts($reservation->start_at, $reservation->end_at, $itemIds, $reservation->room_id, ignoreId: $reservation->id);

        $reservation->approved_by = $admin->id;
        $reservation->approved_at = now();
    }

    private function applyRejection(Reservation $reservation, User $admin, array $data): void
    {
        $reservation->rejection_reason = $data['rejection_reason'];
        $reservation->approved_by = $admin->id; // "reviewed by": who made the decision
    }

    /** FR-R5: hand-over. Records each item's condition_out. */
    private function applyRelease(Reservation $reservation, array $data): void
    {
        $this->ensureItemsStillUsable($reservation);

        foreach ($reservation->equipment as $item) {
            $reservation->equipment()->updateExistingPivot($item->id, [
                'condition_out' => $data['conditions'][$item->id],
            ]);
        }

        $reservation->released_at = now();
    }

    /** FR-R6: return. Records each item's condition_in and the remarks. */
    private function applyReturn(Reservation $reservation, array $data): void
    {
        foreach ($reservation->equipment as $item) {
            $condition = $data['conditions'][$item->id];
            $reservation->equipment()->updateExistingPivot($item->id, ['condition_in' => $condition]);

            // Keep the inventory up to date. "damaged" is handled by a damage report instead (FR-R6, FR-D3).
            if ($condition !== EquipmentCondition::Damaged->value) {
                $item->update(['condition' => $condition]);
            }
        }

        $reservation->returned_at = now();
        $reservation->remarks = $data['remarks'] ?? null;
    }

    /** Items that went under maintenance, were retired, or deleted after the request cannot be approved or handed over. */
    private function ensureItemsStillUsable(Reservation $reservation): void
    {
        $unusable = $reservation->equipment()
            ->get()
            ->filter(fn (Equipment $item) => $item->trashed() || $item->status !== EquipmentStatus::Available);

        if ($unusable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => $unusable->map(fn ($item) => "{$item->asset_tag} {$item->name} is "
                    . ($item->trashed() ? 'deleted' : strtolower($item->status->label())) . ' and cannot be lent out.')->all(),
            ]);
        }

        if ($reservation->room && ! $reservation->room->is_active) {
            throw ValidationException::withMessages(['status' => "{$reservation->room->name} is inactive and cannot be used."]);
        }
    }

    // ---------- helpers ----------

    private function extract(array $data): array
    {
        return [
            Carbon::parse($data['start_at']),
            Carbon::parse($data['end_at']),
            array_map('intval', $data['equipment_ids'] ?? []),
            isset($data['room_id']) ? (int) $data['room_id'] : null,
        ];
    }

    /**
     * Locks the chosen item/room rows until the transaction ends. If two people submit the same item
     * at the same moment, the second one waits here, then sees the first one's reservation in the
     * conflict check, so a double booking cannot slip through.
     */
    private function lockResources(array $itemIds, ?int $roomId): void
    {
        if ($itemIds) {
            Equipment::whereIn('id', $itemIds)->lockForUpdate()->get();
        }
        if ($roomId) {
            Room::whereKey($roomId)->lockForUpdate()->first();
        }
    }

    /** RSV-2026-0105: prefix, year, and the database id (unique by definition, so the reference is too). */
    private function referenceNumberFor(Reservation $reservation): string
    {
        return sprintf('%s-%d-%04d', config('lablend.reference_prefix'), $reservation->created_at->year, $reservation->id);
    }
}
