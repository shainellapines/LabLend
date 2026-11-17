<?php

namespace App\Services;

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
