<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for both "create category" and "edit category".
 * On edit, the unique rules ignore the category being edited (so saving without changes works).
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category'); // null when creating

        return $category
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', Category::class);
    }

    /** Runs before validation: tidy the input so "prj " and "PRJ" are treated the same. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'code' => strtoupper(trim((string) $this->code)),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category)],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters and numbers (e.g. PRJ).',
            'code.unique' => 'Another category already uses this code.',
            'name.unique' => 'A category with this name already exists.',
        ];
    }
}
