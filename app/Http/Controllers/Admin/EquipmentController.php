<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Services\AssetTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin CRUD for equipment (FR-E1, FR-E5). Same pattern as CategoryController, plus a photo upload.
 */
class EquipmentController extends Controller
{
    public function create(AssetTagService $assetTags): View
    {
        $this->authorize('create', Equipment::class);

        $categories = Category::orderBy('name')->get();

        return view('admin.equipment.create', $this->formData($categories) + [
            'equipment' => new Equipment(['condition' => EquipmentCondition::Good, 'status' => EquipmentStatus::Available]),
            'suggestedTags' => $assetTags->nextForAll($categories), // the form fills the tag when a category is picked
        ]);
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo_path'] = $this->storePhoto($request->file('photo'));

        $equipment = Equipment::create($data);

        return redirect()->route('equipment.show', $equipment)
            ->with('success', "{$equipment->asset_tag} \"{$equipment->name}\" was added.");
    }

    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        return view('admin.equipment.edit', $this->formData(Category::orderBy('name')->get()) + [
            'equipment' => $equipment,
            'suggestedTags' => [],
        ]);
    }

    public function update(EquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->storePhoto($request->file('photo'));
            $this->deletePhoto($equipment->photo_path); // replace: remove the old file (section 9.3)
        }

        $equipment->update($data);

        return redirect()->route('equipment.show', $equipment)
            ->with('success', "{$equipment->asset_tag} was updated.");
    }

    /** Soft delete (FR-E5). The photo is kept because the item still appears in its history. */
    public function destroy(Equipment $equipment): RedirectResponse
    {
        $this->authorize('delete', $equipment);

        if ($equipment->reservations()->active()->exists()) {
            return back()->with('error', "{$equipment->asset_tag} has pending, approved, or released reservations and cannot be deleted.");
        }

        $equipment->delete();

        return redirect()->route('equipment.index')
            ->with('success', "{$equipment->asset_tag} was deleted. Its history is kept.");
    }

    // ---------- helpers ----------

    private function formData($categories): array
    {
        return [
            'categories' => $categories,
            'statuses' => EquipmentStatus::cases(),
            'conditions' => EquipmentCondition::forEquipment(),
        ];
    }

    /** Saves the upload to storage/app/public/equipment and returns its path (or null). */
    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store('equipment', 'public');
    }

    private function deletePhoto(?string $path): void
    {
        try {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            report($e); // a leftover file is not worth failing the request for
        }
    }
}
