<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * FR-U2: admin edits a user's details and role.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'id_number' => ['required', 'string', 'max:30', Rule::unique('users')->ignore($user)],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /** FR-U2: an admin cannot demote their own account (there must always be an admin who can log in). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->user()->is($this->route('user')) && $this->input('role') !== UserRole::Admin->value) {
                    $validator->errors()->add('role', 'You cannot remove your own admin role.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return ['id_number.unique' => 'This ID number is already registered.'];
    }

    public function attributes(): array
    {
        return ['id_number' => 'ID number'];
    }
}
