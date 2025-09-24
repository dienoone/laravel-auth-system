<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordResetService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                app(PasswordResetService::class)->getPasswordRules(),
                'confirmed',
                'different:current_password'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Please provide your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'Please provide a new password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.different' => 'New password must be different from current password.',
        ];
    }
}
