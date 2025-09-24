<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordResetService;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                app(PasswordResetService::class)->getPasswordRules(),
                'confirmed'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Reset token is required.',
            'password.required' => 'Please provide a new password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
