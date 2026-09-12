<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use App\Support\WordCounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreDirectArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('publishDirectly', Article::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string', 'min:50'],
            'authors' => ['required', 'string', 'max:2000'],
            'keywords' => ['required', 'string', 'max:500'],
            'issue_id' => ['required', 'integer', Rule::exists('issues', 'id')],
            'page_start' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'page_end' => ['nullable', 'integer', 'min:1', 'max:9999', 'gte:page_start'],
            'pdf' => ['required', File::types(['pdf'])->max(20480)],
            'editorial_confirmation' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $keywords = WordCounter::keywords($this->input('keywords'));

            if (count($keywords) < 4 || count($keywords) > 6) {
                $validator->errors()->add('keywords', 'Enter 4 to 6 keywords, separated by commas.');
            }

            if ($this->authorNames() === []) {
                $validator->errors()->add('authors', 'Enter at least one author name.');
            }
        });
    }

    /**
     * @return list<string>
     */
    public function authorNames(): array
    {
        $raw = (string) $this->input('authors', '');

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function keywordList(): array
    {
        return WordCounter::keywords($this->input('keywords'));
    }
}
