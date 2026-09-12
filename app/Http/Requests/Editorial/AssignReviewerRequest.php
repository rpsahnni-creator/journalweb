<?php

namespace App\Http\Requests\Editorial;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

class AssignReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Article
            && ($this->user()?->can('assignReviewers', $manuscript) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reviewer_id' => ['required', 'integer', 'exists:users,id'],
            'due_at' => ['required', 'date', 'after:today'],
        ];
    }
}
