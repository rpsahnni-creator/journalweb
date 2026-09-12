<?php

namespace App\Http\Requests\Admin;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Issue::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'volume_number' => ['required', 'integer', 'min:1', 'max:9999'],
            'issue_number' => ['required', 'integer', 'min:1', 'max:99'],
            'title' => ['nullable', 'string', 'max:255'],
            'publication_month_year' => ['required', 'string', 'max:64'],
            'is_special_issue' => ['sometimes', 'boolean'],
            'special_issue_theme' => ['nullable', 'string', 'max:255'],
        ];
    }
}
