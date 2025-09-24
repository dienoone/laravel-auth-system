<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage-permissions');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permissionId)
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('permissions', 'slug')->ignore($permissionId),
                'regex:/^[a-z0-9-]+$/'
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required.',
            'name.unique' => 'A permission with this name already exists.',
            'slug.unique' => 'A permission with this slug already exists.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
        ];
    }
}
