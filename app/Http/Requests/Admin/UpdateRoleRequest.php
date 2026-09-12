<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && ($this->user()?->can('update', $role) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $protected = $role instanceof Role && RoleSlug::tryFrom($role->slug) !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('roles', 'slug')->ignore($role?->id),
                Rule::prohibitedIf($protected && $this->input('slug') !== $role?->slug),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }
}
