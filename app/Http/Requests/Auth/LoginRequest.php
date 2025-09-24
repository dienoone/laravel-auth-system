<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'login' => ['required', 'string'], // Can be email or username
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
            'device_name' => ['string', 'max:255']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Please enter your email or username.',
            'password.required' => 'Please enter your password.',
        ];
    }

    /**
     * Get the login field (email or username)
     */
    public function getLoginField(): string
    {
        $login = $this->input('login');

        // Check if login input is email
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /**
     * Get credentials for authentication
     */
    public function getCredentials(): array
    {
        return [
            $this->getLoginField() => $this->input('login'),
            'password' => $this->input('password'),
        ];
    }
}
