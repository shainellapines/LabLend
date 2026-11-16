<?php

namespace App\Http\Requests;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating and editing equipment (FR-E1).
 */
class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $equipment = $this->route('equipment'); // null when creating

        return $equipment
            ? $this->user()->can('update', $equipment)
            : $this->user()->can('create', Equipment::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['asset_tag' => strtoupper(trim((string) $this->asset_tag))]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', Rule::exists('categories', 'id')],
            // The unique rule checks the table directly, so tags of soft-deleted items count too (no reuse).
            'asset_tag' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('equipment')->ignore($this->route('equipment'))],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(array_column(EquipmentCondition::forEquipment(), 'value'))],
            'status' => ['required', Rule::enum(EquipmentStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // max in KB = 2 MB
        ];
    }

    public function messages(): array
    {
        return [
            'asset_tag.unique' => 'This asset tag is already used by another item.',
            'asset_tag.regex' => 'Use letters, numbers, and dashes only (e.g. PRJ-0004).',
            'photo.max' => 'The photo must not be larger than 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['category_id' => 'category'];
    }
}
