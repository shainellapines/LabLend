<?php

namespace App\Http\Requests;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating and editing rooms (FR-RM1).
 */
class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = $this->route('room'); // null when creating

        return $room
            ? $this->user()->can('update', $room)
            : $this->user()->can('create', Room::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->code)),
            // An unchecked checkbox sends nothing, so turn "missing" into false.
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('rooms')->ignore($this->route('room'))],
            'type' => ['required', Rule::enum(RoomType::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Another room already uses this code.',
            'code.regex' => 'Use letters, numbers, and dashes only (e.g. LAB-2).',
        ];
    }
}
