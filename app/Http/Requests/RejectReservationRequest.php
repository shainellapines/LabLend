<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** FR-R4: rejecting needs a reason (shown to the borrower). */
class RejectReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('reservation'));
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['rejection_reason' => 'reason'];
    }
}
