<?php

namespace Database\Seeders;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use App\Enums\EquipmentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * About 100 reservations from 30 days ago to 7 days ahead, so lists and charts have data.
 *
 * Guarantees for the demo (CLAUDE.md section 14):
 *  - no item or room is double-booked (each is used at most once per day);
 *  - exactly ONE overdue reservation (released, ended yesterday, never returned);
 *  - one returned reservation with a "damaged" item + an open major damage report (item under maintenance);
 *  - one item currently out with a faculty member (released, due in 2 days).
 *
 * fake()->seed() makes the random choices the same every time, so every teammate gets identical demo data.
 */
class ReservationSeeder extends Seeder
{
    /** Two-hour time slots used for generated reservations. */
    private const SLOTS = [[8, 10], [10, 12], [13, 15], [15, 17]];

    private const PURPOSES = [
        'Class presentation', 'Thesis defense', 'Lab exercise', 'Capstone demo', 'Department seminar',
        'Org meeting', 'Quiz bee', 'Faculty meeting', 'Programming workshop', 'Network cabling activity',
    ];

    private User $admin;

    /** Planned reservations; they are sorted by date and saved at the end so reference numbers are in order. */
    private array $plans = [];

    /** Items/rooms already used per day: ['2026-11-15' => ['items' => [ids], 'rooms' => [ids]]] */
    private array $usedPerDay = [];

    public function run(): void
    {
        fake()->seed(2026);

        $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
        $borrowers = User::where('role', '!=', UserRole::Admin)->get();
        $faculty = $borrowers->where('role', UserRole::Faculty)->values();
        $students = $borrowers->where('role', UserRole::Student)->values();

        $today = CarbonImmutable::today();
        $items = Equipment::where('status', EquipmentStatus::Available)->get();
        $rooms = Room::all();

        // ---- 1. Special demo reservations (planned first so random ones avoid their items) ----

        // Overdue: student borrowed a projector yesterday 1–3 PM and never returned it.
        // Not student@lablend.test: that account must be able to make a new reservation in demo step 4.
        $overdueBorrower = $students->last(); // student5@lablend.test (Paolo Ramos)
        $overdueItem = $items->firstWhere('asset_tag', 'PRJ-0002');
        $this->plan($overdueBorrower, [$overdueItem], null,
            $today->subDay()->setTime(13, 0), $today->subDay()->setTime(15, 0),
            ReservationStatus::Released, 'Class presentation');

        // Currently out: faculty borrowed a laptop this morning for 3 days (within the 72-hour faculty limit).
        $longItem = $items->firstWhere('asset_tag', 'LAP-0001');
        $this->plan($faculty->first(), [$longItem], null,
            $today->setTime(7, 0), $today->addDays(2)->setTime(17, 0),
            ReservationStatus::Released, 'Research data gathering');

        // Damaged on return 5 days ago: a speaker came back with a torn cone.
        $damagedItem = $items->firstWhere('asset_tag', 'AUD-0001');
        $damagePlanIndex = $this->plan($students[1], [$damagedItem, $items->firstWhere('asset_tag', 'EXT-0001')], null,
            $today->subDays(5)->setTime(13, 0), $today->subDays(5)->setTime(17, 0),
            ReservationStatus::Returned, 'Org general assembly');
        $this->plans[$damagePlanIndex]['damaged'] = [$damagedItem->id];

        // Items that are out or broken are never used by the generated reservations.
        $randomItems = $items->reject(fn ($item) => in_array($item->id, [$overdueItem->id, $longItem->id, $damagedItem->id]));

        // ---- 2. Generated reservations, 3 per day (no Sundays) ----

        for ($offset = -30; $offset <= 7; $offset++) {
            $day = $today->addDays($offset);
            if ($day->isSunday()) {
                continue;
            }

            foreach (fake()->randomElements(self::SLOTS, 3) as [$fromHour, $toHour]) {
                $start = $day->setTime($fromHour, 0);
                $end = $day->setTime($toHour, 0);

                // Today: skip slots that are in progress right now (keeps exactly one overdue reservation).
                if ($start->isPast() && $end->isFuture()) {
                    continue;
                }

                $status = $this->statusFor($start, $end);

                // Future reservations: overdue borrower is blocked (FR-R3), students max 3 active.
                $pool = $end->isFuture()
                    ? $borrowers->reject(fn ($u) => $u->is($overdueBorrower) || ($u->role === UserRole::Student && $this->activeCount($u) >= 3))
                    : $borrowers;

                $chosenItems = $this->freeItems($randomItems, $day, fake()->numberBetween(1, 3));
                $room = fake()->boolean(25) ? $this->freeRoom($rooms, $day) : null;

                if ($chosenItems === [] && ! $room) {
                    continue;
                }

                $this->plan(fake()->randomElement($pool->all()), $chosenItems, $room, $start, $end, $status,
                    fake()->randomElement(self::PURPOSES));
            }
        }

        // ---- 3. Save in date order ----

        usort($this->plans, fn ($a, $b) => $a['start'] <=> $b['start']);

        foreach ($this->plans as $number => $plan) {
            $this->save($plan, $number + 1);
        }

        // The damaged speaker is now under maintenance (FR-D3).
        $damagedItem->update(['status' => EquipmentStatus::UnderMaintenance]);
    }

    /** Status that makes sense for the reservation's time: past ones mostly returned, future ones pending/approved. */
    private function statusFor(CarbonImmutable $start, CarbonImmutable $end): ReservationStatus
    {
        if ($end->isPast()) {
            return fake()->randomElement([
                ReservationStatus::Returned, ReservationStatus::Returned, ReservationStatus::Returned,
                ReservationStatus::Returned, ReservationStatus::Returned, ReservationStatus::Returned,
                ReservationStatus::Returned, ReservationStatus::Cancelled, ReservationStatus::Rejected,
            ]);
        }

        return fake()->randomElement([ReservationStatus::Pending, ReservationStatus::Approved]);
    }

    /** Records a planned reservation and marks its items/room as used on that day. Returns its index. */
    private function plan(User $user, array $items, ?Room $room, CarbonImmutable $start, CarbonImmutable $end,
                          ReservationStatus $status, string $purpose): int
    {
        $key = $start->toDateString();
        foreach ($items as $item) {
            $this->usedPerDay[$key]['items'][] = $item->id;
        }
        if ($room) {
            $this->usedPerDay[$key]['rooms'][] = $room->id;
        }

        $this->plans[] = compact('user', 'items', 'room', 'start', 'end', 'status', 'purpose') + ['damaged' => []];

        return array_key_last($this->plans);
    }

    private function freeItems(Collection $items, CarbonImmutable $day, int $count): array
    {
        $used = $this->usedPerDay[$day->toDateString()]['items'] ?? [];
        $free = $items->reject(fn ($item) => in_array($item->id, $used))->all();

        return fake()->randomElements($free, min($count, count($free)));
    }

    private function freeRoom(Collection $rooms, CarbonImmutable $day): ?Room
    {
        $used = $this->usedPerDay[$day->toDateString()]['rooms'] ?? [];
        $free = $rooms->reject(fn ($room) => in_array($room->id, $used))->all();

        return $free ? fake()->randomElement($free) : null;
    }

    /** Planned active (pending/approved/released) reservations of a user that have not ended yet. */
    private function activeCount(User $user): int
    {
        return collect($this->plans)
            ->filter(fn ($p) => $p['user']->is($user)
                && in_array($p['status']->value, ReservationStatus::activeValues())
                && $p['end']->isFuture())
            ->count();
    }

    private function save(array $plan, int $number): void
    {
        $status = $plan['status'];
        $reachedApproval = in_array($status, [ReservationStatus::Approved, ReservationStatus::Released, ReservationStatus::Returned]);
        $reachedRelease = in_array($status, [ReservationStatus::Released, ReservationStatus::Returned]);
        $createdAt = $plan['start']->subDays(fake()->numberBetween(1, 3))->setTime(fake()->numberBetween(8, 17), fake()->numberBetween(0, 59));

        $reservation = Reservation::factory()->create([
            'reference_no' => sprintf('%s-%d-%04d', config('lablend.reference_prefix'), $plan['start']->year, $number),
            'user_id' => $plan['user']->id,
            'room_id' => $plan['room']?->id,
            'purpose' => $plan['purpose'],
            'start_at' => $plan['start'],
            'end_at' => $plan['end'],
            'status' => $status,
            'approved_by' => $reachedApproval || $status === ReservationStatus::Rejected ? $this->admin->id : null,
            'approved_at' => $reachedApproval ? $createdAt->addHours(2) : null,
            'rejection_reason' => $status === ReservationStatus::Rejected ? 'Item needed for a department event at that time.' : null,
            'released_at' => $reachedRelease ? $plan['start'] : null,
            'returned_at' => $status === ReservationStatus::Returned ? $plan['end'] : null,
            'remarks' => $plan['damaged'] ? 'Speaker returned with a torn cone and distorted sound.' : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        foreach ($plan['items'] as $item) {
            $reservation->equipment()->attach($item->id, [
                'condition_out' => $reachedRelease ? 'good' : null,
                'condition_in' => $status === ReservationStatus::Returned
                    ? (in_array($item->id, $plan['damaged']) ? 'damaged' : 'good')
                    : null,
            ]);
        }

        foreach ($plan['damaged'] as $equipmentId) {
            DamageReport::factory()->create([
                'equipment_id' => $equipmentId,
                'reservation_id' => $reservation->id,
                'reported_by' => $this->admin->id,
                'description' => 'Speaker cone is torn; sound is distorted at medium volume.',
                'severity' => DamageSeverity::Major,
                'status' => DamageStatus::Open,
                'created_at' => $plan['end'],
                'updated_at' => $plan['end'],
            ]);
        }
    }
}
