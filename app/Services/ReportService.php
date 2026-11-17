<?php

namespace App\Services;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use App\Enums\EquipmentStatus;
use App\Enums\ReservationStatus;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Queries for the dashboards (step 3.5) and the reports page (step 3.6).
 * Kept out of controllers and views so each number is calculated in exactly one place.
 */
class ReportService
{
    // ================= Admin dashboard (FR-H1) =================

    /** The five KPI tiles. */
    public function adminStats(): array
    {
        return [
            'pending' => Reservation::where('status', ReservationStatus::Pending)->count(),
            // Items (not reservations) that are out right now.
            'borrowedItems' => Equipment::whereHas('reservations', fn ($q) => $q->where('reservations.status', ReservationStatus::Released))->count(),
            'overdue' => Reservation::overdue()->count(),
            'openDamage' => DamageReport::where('status', '!=', DamageStatus::Resolved)->count(),
            'underMaintenance' => Equipment::where('status', EquipmentStatus::UnderMaintenance)->count(),
        ];
    }

    /** Today's work for the custodian: approved reservations to hand over, and borrowed ones due back. */
    public function todaysSchedule(): array
    {
        $with = ['user', 'equipment', 'room'];

        return [
            'releases' => Reservation::where('status', ReservationStatus::Approved)
                ->whereDate('start_at', today())->with($with)->orderBy('start_at')->get(),
            'returns' => Reservation::where('status', ReservationStatus::Released)
                ->whereDate('end_at', today())->with($with)->orderBy('end_at')->get(),
        ];
    }

    /**
     * Bar chart: items handed over most often in the last $days days.
     * Returns a collection of Equipment with a borrow_count attribute.
     */
    public function topBorrowedItems(int $days = 30, int $limit = 5): Collection
    {
        return Equipment::withTrashed()
            ->withCount(['reservations as borrow_count' => fn ($q) => $q
                ->whereIn('reservations.status', [ReservationStatus::Released, ReservationStatus::Returned])
                ->where('start_at', '>=', now()->subDays($days))])
            ->orderByDesc('borrow_count')
            ->orderBy('asset_tag')
            ->limit($limit)
            ->get()
            ->filter(fn (Equipment $item) => $item->borrow_count > 0)
            ->values();
    }

    /**
     * Line chart: how many reservation requests were made each day, for the last $days days.
     * Days without any request are included as 0, so the line has no gaps.
     * Returns ['2026-11-04' => 3, '2026-11-05' => 0, ...] (oldest first).
     */
    public function reservationsPerDay(int $days = 14): array
    {
        $from = today()->subDays($days - 1);

        $counts = Reservation::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[$day] = (int) ($counts[$day] ?? 0);
        }

        return $series;
    }

    // ================= Reports page (FR-H3) =================

    /**
     * Usage report: how many times each item was handed over (released or returned) between $from and $to.
     * Includes items borrowed 0 times, so the custodian can also see what is never used.
     */
    public function usageByItem(Carbon $from, Carbon $to): Collection
    {
        return Equipment::withTrashed()
            ->with('category')
            ->withCount(['reservations as borrow_count' => fn ($q) => $q
                ->whereIn('reservations.status', [ReservationStatus::Released, ReservationStatus::Returned])
                ->whereBetween('start_at', [$from, $to])])
            ->orderByDesc('borrow_count')
            ->orderBy('asset_tag')
            ->get();
    }

    /** Same numbers added up per category. Collection of ['category' => 'Projectors', 'items' => 5, 'borrow_count' => 23]. */
    public function usageByCategory(Collection $usageByItem): Collection
    {
        return $usageByItem
            ->groupBy(fn (Equipment $item) => $item->category->name)
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'items' => $items->count(),
                'borrow_count' => $items->sum('borrow_count'),
            ])
            ->sortByDesc('borrow_count')
            ->values();
    }

    /** Overdue report: everything that should have come back by now. */
    public function overdueList(): Collection
    {
        return Reservation::overdue()->with('user', 'equipment', 'room')->orderBy('end_at')->get();
    }

    /** Damage summary: counts per severity and per status, plus the reports themselves, for the date range. */
    public function damageSummary(Carbon $from, Carbon $to): array
    {
        $reports = DamageReport::with('equipment', 'reporter')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        return [
            'reports' => $reports,
            'bySeverity' => collect(DamageSeverity::cases())->mapWithKeys(fn ($s) => [$s->label() => $reports->where('severity', $s)->count()]),
            'byStatus' => collect(DamageStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => $reports->where('status', $s)->count()]),
        ];
    }

    /**
     * One report as a plain table, used by BOTH exports (PDF and CSV), so they always contain the same data.
     * Returns ['title' => ..., 'headers' => [...], 'rows' => [[...], ...]].
     */
    public function table(string $type, Carbon $from, Carbon $to): array
    {
        $period = $from->format('M d, Y') . ' – ' . $to->format('M d, Y');
        $date = 'M d, Y g:i A';

        return match ($type) {
            'usage' => [
                'title' => "Equipment usage ({$period})",
                'headers' => ['Asset tag', 'Item', 'Category', 'Times borrowed'],
                'rows' => $this->usageByItem($from, $to)
                    ->map(fn ($i) => [$i->asset_tag, $i->name . ($i->trashed() ? ' (deleted)' : ''), $i->category->name, $i->borrow_count])->all(),
            ],
            'overdue' => [
                'title' => 'Overdue reservations (as of ' . now()->format($date) . ')',
                'headers' => ['Reference', 'Borrower', 'ID number', 'Items', 'Room', 'Was due', 'Days overdue'],
                'rows' => $this->overdueList()->map(fn ($r) => [
                    $r->reference_no, $r->user->name, $r->user->id_number,
                    $r->equipment->pluck('asset_tag')->implode(', ') ?: '—', $r->room?->name ?? '—',
                    $r->end_at->format($date), (int) $r->end_at->diffInDays(now()),
                ])->all(),
            ],
            'damage' => [
                'title' => "Damage reports ({$period})",
                'headers' => ['Reported', 'Asset tag', 'Item', 'Severity', 'Status', 'Reported by', 'Description'],
                'rows' => $this->damageSummary($from, $to)['reports']->map(fn ($d) => [
                    $d->created_at->format($date), $d->equipment->asset_tag, $d->equipment->name,
                    $d->severity->label(), $d->status->label(), $d->reporter->name, $d->description,
                ])->all(),
            ],
        };
    }

    // ================= Borrower dashboard (FR-H2) =================

    public function borrowerSummary(User $user): array
    {
        $mine = fn () => $user->reservations()->with('equipment', 'room');

        $active = $mine()->forTab('active')->orderBy('end_at')->get();

        return [
            'upcoming' => $mine()->forTab('upcoming')->orderBy('start_at')->limit(5)->get(),
            'upcomingCount' => $user->reservations()->forTab('upcoming')->count(),
            'active' => $active,
            'itemsWithMe' => $active->sum(fn (Reservation $r) => $r->equipment->count()),
            'overdue' => $active->filter->isOverdue()->count(),
        ];
    }
}
