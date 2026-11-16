<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Equipment pages every logged-in user can see. Changes are in Admin\EquipmentController.
 */
class EquipmentController extends Controller
{
    /** FR-E2: search + filters + 15 per page. */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $equipment = Equipment::with('category') // eager load: 1 extra query instead of 1 per row (no N+1)
            ->filter($request->only('search', 'category', 'status', 'condition'))
            ->orderBy('asset_tag')
            ->paginate(15)
            ->withQueryString();

        return view('equipment.index', [
            'equipment' => $equipment,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'statuses' => EquipmentStatus::cases(),
            'conditions' => EquipmentCondition::forEquipment(),
        ]);
    }
}
