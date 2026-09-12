<?php

namespace App\Http\Requests\Editorial;

use App\Models\Issue;
use App\Models\IssueArticle;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueArticleRequest extends FormRequest
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
            'article_number' => ['nullable', 'string', 'max:64'],
            'page_start' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'page_end' => ['nullable', 'integer', 'min:1', 'max:99999', 'gte:page_start'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }

    public function placement(): IssueArticle
    {
        $issue = $this->route('issue');
        $placement = $this->route('placement');

        abort_unless($issue instanceof Issue && $placement instanceof IssueArticle, 404);
        abort_unless($placement->issue_id === $issue->id, 404);

        return $placement;
    }
}
