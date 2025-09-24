<?php

namespace App\Http\Requests\Auth\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class EnableTwoFactorRequest extends FormRequest
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
            'password' => ['required', 'string', 'current_password'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Please provide your password.',
            'password.current_password' => 'The password is incorrect.',
        ];
    }
}
