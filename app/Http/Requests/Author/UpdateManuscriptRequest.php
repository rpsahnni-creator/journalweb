<?php

namespace App\Http\Requests\Author;

use App\Enums\ArticleType;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Article
            && ($this->user()?->can('updateAsAuthor', $manuscript) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'article_type' => ['required', Rule::enum(ArticleType::class)],
            'abstract' => ['nullable', 'string', 'max:10000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'language' => ['nullable', 'string', 'max:10'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'originality_confirmed' => ['sometimes', 'boolean'],
            'conflict_of_interest_declared' => ['sometimes', 'boolean'],
            'conflict_of_interest_statement' => ['nullable', 'string', 'max:5000'],
            'author_response' => ['nullable', 'string', 'max:20000'],
            'authors' => ['required', 'array', 'min:1', 'max:25'],
            'authors.*.name' => ['required', 'string', 'max:255'],
            'authors.*.email' => ['nullable', 'email', 'max:255'],
            'authors.*.affiliation' => ['nullable', 'string', 'max:255'],
            'authors.*.orcid' => ['nullable', 'string', 'max:19', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'corresponding_index' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $authors = $this->input('authors', []);
            $index = (int) $this->input('corresponding_index', 0);

            if (! is_array($authors) || $authors === []) {
                return;
            }

            if (! array_key_exists($index, $authors)) {
                $validator->errors()->add('corresponding_index', 'Select a corresponding author from the author list.');
            }
        });
    }
}
