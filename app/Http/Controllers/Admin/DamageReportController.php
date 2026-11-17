<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDamageReportRequest;
use App\Models\DamageReport;
use App\Services\DamageReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * FR-D2: admin updates a report's status (open -> under repair -> resolved) or deletes it.
 */
class DamageReportController extends Controller
{
    public function update(UpdateDamageReportRequest $request, DamageReport $damageReport, DamageReportService $damageReports): RedirectResponse
    {
        $damageReports->update($damageReport, $request->validated(), $request->boolean('make_available'));

        return redirect()->route('damage-reports.show', $damageReport)
            ->with('success', "Report updated: {$damageReport->status->label()}.");
    }

    public function destroy(DamageReport $damageReport): RedirectResponse
    {
        $this->authorize('delete', $damageReport);

        $damageReport->delete();

        try {
            if ($damageReport->photo_path) {
                Storage::disk('public')->delete($damageReport->photo_path); // section 9.3: remove the file too
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('damage-reports.index')->with('success', 'Damage report was deleted.');
    }
}
