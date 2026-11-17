<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-H3: reports page (usage, overdue, damage) with PDF and CSV export.
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index(ReportRequest $request): View
    {
        [$from, $to] = $request->dateRange();
        $usage = $this->reports->usageByItem($from, $to);

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'topItems' => $usage->take(10),
            'byCategory' => $this->reports->usageByCategory($usage),
            'totalBorrows' => $usage->sum('borrow_count'),
            'neverUsed' => $usage->where('borrow_count', 0)->count(),
            'overdue' => $this->reports->overdueList(),
            'damage' => $this->reports->damageSummary($from, $to),
        ]);
    }

    /** GET /admin/reports/{type}/export?format=pdf|csv&from=…&to=… ({type} is limited to usage|overdue|damage in the route). */
    public function export(ReportRequest $request, string $type): Response
    {
        [$from, $to] = $request->dateRange();
        $table = $this->reports->table($type, $from, $to);
        $filename = "lablend-{$type}-report-" . now()->format('Y-m-d');

        if ($request->input('format') === 'csv') {
            return $this->csv($table, "{$filename}.csv");
        }

        try {
            return Pdf::loadView('pdf.report', $table)->setPaper('a4', 'landscape')->download("{$filename}.pdf");
        } catch (\Throwable $e) {
            report($e); // section 9.5
            return back()->with('error', 'The PDF could not be created. Please try again.');
        }
    }

    /** CSV without a package: stream the rows straight to the browser with PHP's fputcsv(). */
    private function csv(array $table, string $filename): Response
    {
        return response()->streamDownload(function () use ($table) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 marker so Excel shows "–" and "ñ" correctly
            fputcsv($out, $table['headers']);
            foreach ($table['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
