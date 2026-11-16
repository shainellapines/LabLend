<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * Validation for the registration form. Laravel runs these rules automatically before
 * RegisteredUserController@store is called; if they fail, the user is sent back with errors.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guests are allowed to register
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:30', 'unique:'.User::class],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'id_number.unique' => 'This ID number is already registered.',
        ];
    }

    public function attributes(): array
    {
        return ['id_number' => 'ID number'];
    }
}
