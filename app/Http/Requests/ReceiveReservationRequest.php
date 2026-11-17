<?php

namespace App\Http\Requests;

use App\Enums\EquipmentCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-R6: return. A condition (good/fair/poor/damaged) for every item, plus optional remarks.
 */
class ReceiveReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('reservation'));
    }

    public function rules(): array
    {
        $rules = ['remarks' => ['nullable', 'string', 'max:2000']];
        foreach ($this->route('reservation')->equipment as $item) {
            $rules["conditions.{$item->id}"] = ['required', Rule::enum(EquipmentCondition::class)];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return $this->route('reservation')->equipment
            ->mapWithKeys(fn ($item) => ["conditions.{$item->id}" => "condition of {$item->asset_tag}"])
            ->all();
    }
}
