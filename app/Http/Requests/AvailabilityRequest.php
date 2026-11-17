<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Input for GET /reservations/availability (the live hints in the reservation form).
 */
class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any logged-in user (route is behind auth + active)
    }

    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'ignore' => ['nullable', 'integer'], // the reservation being edited, so it does not clash with itself
        ];
    }
}
