<?php

namespace App\Http\Requests\Author;

use App\Enums\ArticleType;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Article::class) ?? false;
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
        ];
    }
}
