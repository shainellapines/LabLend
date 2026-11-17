<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GET /dashboard: the admin sees the lab's operations + charts (FR-H1); borrowers see their own summary (FR-H2).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): View
    {
        if ($request->user()->isAdmin()) {
            $perDay = $reports->reservationsPerDay(14);
            $top = $reports->topBorrowedItems(30, 5);

            return view('dashboard.admin', [
                'stats' => $reports->adminStats(),
                'today' => $reports->todaysSchedule(),
                // Chart data is prepared here so the view only displays it.
                'perDayChart' => [
                    'labels' => array_map(fn ($day) => date('M j', strtotime($day)), array_keys($perDay)),
                    'values' => array_values($perDay),
                ],
                'topChart' => [
                    'labels' => $top->map(fn ($item) => "{$item->asset_tag} {$item->name}")->all(),
                    'values' => $top->pluck('borrow_count')->all(),
                ],
            ]);
        }

        return view('dashboard.borrower', $reports->borrowerSummary($request->user()));
    }
}
