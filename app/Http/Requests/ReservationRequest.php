<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation for creating and editing a reservation (FR-R1, FR-R3).
 * Rules that need other reservations (conflicts, overdue, student limit) are in ReservationService.
 */
class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation'); // null when creating

        return $reservation
            ? $this->user()->can('update', $reservation)
            : $this->user()->can('create', Reservation::class);
    }

    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],

            // At least one item OR a room (FR-R1).
            'equipment_ids' => ['nullable', 'array', 'required_without:room_id'],
            // Each item must exist, not be deleted, and have status "available" (FR-E6).
            'equipment_ids.*' => ['integer', 'distinct',
                Rule::exists('equipment', 'id')->where('status', 'available')->whereNull('deleted_at')],
            // The room must exist and be active (FR-RM2).
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')->where('is_active', true)],
        ];
    }

    /**
     * Extra checks that run after the rules above pass (FR-R3). All numbers come from config/lablend.php.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['start_at', 'end_at'])) {
                    return; // dates are missing or invalid: nothing to compare
                }

                $start = Carbon::parse($this->input('start_at'));
                $end = Carbon::parse($this->input('end_at'));

                $lead = config('lablend.min_lead_minutes');
                if ($start->lt(now()->addMinutes($lead))) {
                    $validator->errors()->add('start_at', "Reservations must start at least {$lead} minutes from now.");
                }

                $maxDays = config('lablend.max_days_ahead');
                if ($start->gt(now()->addDays($maxDays)->endOfDay())) {
                    $validator->errors()->add('start_at', "You can reserve at most {$maxDays} days ahead.");
                }

                $role = $this->user()->role;
                $maxHours = config("lablend.max_hours.{$role->value}");
                if ($start->diffInMinutes($end) > $maxHours * 60) {
                    $validator->errors()->add('end_at', "{$role->label()} reservations can be at most {$maxHours} hours long.");
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'end_at.after' => 'The end time must be after the start time.',
            'equipment_ids.required_without' => 'Select at least one item or a room.',
            'equipment_ids.*.exists' => 'One of the selected items is no longer available for reservation.',
            'equipment_ids.*.distinct' => 'The same item was selected twice.',
            'room_id.exists' => 'The selected room is not available for reservation.',
        ];
    }

    public function attributes(): array
    {
        return ['start_at' => 'start time', 'end_at' => 'end time'];
    }
}
