<?php

namespace App\Http\Requests\Editorial;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class AssignIssueArticleRequest extends FormRequest
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
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'article_number' => ['nullable', 'string', 'max:64'],
            'page_start' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'page_end' => ['nullable', 'integer', 'min:1', 'max:99999', 'gte:page_start'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
