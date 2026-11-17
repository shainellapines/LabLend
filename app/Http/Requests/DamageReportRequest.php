<?php

namespace App\Http\Requests;

use App\Enums\DamageSeverity;
use App\Models\DamageReport;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * FR-D1: new damage report.
 */
class DamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DamageReport::class);
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'integer', Rule::exists('equipment', 'id')->whereNull('deleted_at')],
            'reservation_id' => ['nullable', 'integer', Rule::exists('reservations', 'id')],
            'severity' => ['required', Rule::enum(DamageSeverity::class)],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** The linked reservation must include the item, and borrowers may only link their own reservations. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('reservation_id')) {
                    return;
                }

                $reservation = Reservation::find($this->integer('reservation_id'));

                if (! $this->user()->isAdmin() && (int) $reservation->user_id !== $this->user()->id) {
                    $validator->errors()->add('reservation_id', 'You can only link your own reservations.');
                } elseif (! $reservation->equipment()->whereKey($this->integer('equipment_id'))->exists()) {
                    $validator->errors()->add('reservation_id', 'That reservation does not include the selected item.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return ['photo.max' => 'The photo must not be larger than 2 MB.'];
    }

    public function attributes(): array
    {
        return ['equipment_id' => 'item', 'reservation_id' => 'reservation'];
    }
}
