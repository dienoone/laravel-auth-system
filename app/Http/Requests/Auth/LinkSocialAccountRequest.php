<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LinkSocialAccountRequest extends FormRequest
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
            'provider' => ['required', 'string', 'in:google,github,facebook'],
            'access_token' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Provider is required.',
            'provider.in' => 'Invalid provider. Allowed: google, github, facebook.',
            'access_token.required' => 'Access token is required.',
        ];
    }
}
