<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrLabelRequest;
use App\Models\Category;
use App\Models\Equipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-E4: printable QR label sheet (PDF) for selected items or a whole category.
 */
class QrLabelController extends Controller
{
    /** The page where the admin picks a category or items. */
    public function index(): View
    {
        $this->authorize('create', Equipment::class);

        return view('admin.equipment.qr-labels', [
            'categories' => Category::withCount('equipment')->orderBy('name')->get(),
            'itemsByCategory' => Equipment::with('category')->orderBy('asset_tag')->get()->groupBy('category.name'),
        ]);
    }

    /** Builds the PDF. Each label's QR code contains the item's URL (route('equipment.show')). */
    public function pdf(QrLabelRequest $request): Response
    {
        $items = Equipment::with('category')
            ->when($request->filled('ids'), fn ($q) => $q->whereIn('id', $request->validated('ids')))
            ->when(! $request->filled('ids'), fn ($q) => $q->where('category_id', $request->validated('category')))
            ->orderBy('asset_tag')
            ->get();

        try {
            return Pdf::loadView('pdf.qr-labels', ['items' => $items])
                ->setPaper('a4')
                ->stream('lablend-qr-labels.pdf'); // opens in the browser's PDF viewer, ready to print
        } catch (\Throwable $e) {
            report($e); // section 9.5: log the details, show a friendly message
            return back()->with('error', 'The label sheet could not be created. Please try again.');
        }
    }
}
