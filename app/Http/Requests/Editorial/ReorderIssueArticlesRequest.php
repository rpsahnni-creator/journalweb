<?php

namespace App\Http\Requests\Editorial;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class ReorderIssueArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue
            && ($this->user()?->can('assignArticles', $issue) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
