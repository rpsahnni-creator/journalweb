<?php

namespace App\Http\Requests\Admin;

use App\Models\EditorialBoardMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditorialBoardMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof EditorialBoardMember
            && ($this->user()?->can('update', $member) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role_title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'official_address' => ['nullable', 'string', 'max:1000'],
            'country' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:255', $this->institutionalEmailRule()],
            'bio' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function institutionalEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! filled($value) || str_starts_with((string) $value, '[')) {
                return;
            }

            if (! filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                $fail('The email must be a valid email address.');
            }
        };
    }
}
