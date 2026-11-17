<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-E4: which labels to print, either a whole category or hand-picked items.
 */
class QrLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Equipment::class); // admin only
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id'), 'required_without:ids'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', Rule::exists('equipment', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return ['category.required_without' => 'Choose a category or tick at least one item.'];
    }
}
