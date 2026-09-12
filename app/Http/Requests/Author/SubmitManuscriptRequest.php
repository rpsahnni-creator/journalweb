<?php

namespace App\Http\Requests\Author;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

class SubmitManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Article
            && ($this->user()?->can('submit', $manuscript) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $manuscript = $this->route('manuscript');
        $isRevision = $manuscript instanceof Article
            && $manuscript->status === ArticleStatus::RevisionRequired;

        return [
            'author_response' => $isRevision
                ? ['required', 'string', 'min:20', 'max:20000']
                : ['nullable', 'string', 'max:20000'],
        ];
    }
}
