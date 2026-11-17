<?php

namespace App\Http\Requests;

use App\Enums\DamageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * FR-D2: admin updates a report's status and resolution notes.
 */
class UpdateDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('damageReport'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DamageStatus::class)],
            'resolution_notes' => ['nullable', 'string', 'max:2000', 'required_if:status,resolved'],
            'make_available' => ['boolean'],
        ];
    }

    /** Reports only move forward: open -> under repair -> resolved (see DamageStatus::canMoveTo()). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $current = $this->route('damageReport')->status;
                $next = DamageStatus::tryFrom((string) $this->input('status'));

                if ($next && ! $current->canMoveTo($next)) {
                    $validator->errors()->add('status', "A report that is {$current->label()} cannot go back to {$next->label()}.");
                }
            },
        ];
    }

    public function messages(): array
    {
        return ['resolution_notes.required_if' => 'Describe how it was resolved (e.g. "Replaced the cable").'];
    }
}
