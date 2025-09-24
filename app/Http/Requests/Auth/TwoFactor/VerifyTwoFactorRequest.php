<?php

namespace App\Http\Requests\Auth\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
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
            'two_factor_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'two_factor_token.required' => 'Two-factor token is required.',
            'code.required' => 'Please provide the two-factor authentication code.',
        ];
    }
}
