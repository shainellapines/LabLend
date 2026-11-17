<?php

namespace App\Http\Requests;

use App\Enums\EquipmentCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-R5: hand-over. One condition (good/fair/poor) is required for EVERY item in the reservation.
 * The form sends them as conditions[<equipment id>] = 'good'.
 */
class ReleaseReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('reservation'));
    }

    public function rules(): array
    {
        $allowed = array_column(EquipmentCondition::forEquipment(), 'value'); // no "damaged" at hand-over

        $rules = [];
        foreach ($this->route('reservation')->equipment as $item) {
            $rules["conditions.{$item->id}"] = ['required', Rule::in($allowed)];
        }

        return $rules;
    }

    /** Error messages say "condition of PRJ-0001" instead of "conditions.5". */
    public function attributes(): array
    {
        return $this->route('reservation')->equipment
            ->mapWithKeys(fn ($item) => ["conditions.{$item->id}" => "condition of {$item->asset_tag}"])
            ->all();
    }
}
