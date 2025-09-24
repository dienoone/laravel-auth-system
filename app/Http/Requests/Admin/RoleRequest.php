<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage-roles');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId)
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles', 'slug')->ignore($roleId),
                'regex:/^[a-z0-9-]+$/'
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'slug.unique' => 'A role with this slug already exists.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'permissions.*.exists' => 'Invalid permission selected.',
        ];
    }
}
