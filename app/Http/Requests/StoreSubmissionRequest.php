<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Rules\KeywordCount;
use App\Rules\WordCount;
use App\Support\ManuscriptUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Submission::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $config = config('submissions.manuscript');

        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => [
                'required',
                'string',
                new WordCount(
                    (int) $config['abstract_min_words'],
                    (int) $config['abstract_max_words'],
                ),
            ],
            'keywords' => [
                'required',
                'string',
                'max:500',
                new KeywordCount(
                    (int) $config['keywords_min'],
                    (int) $config['keywords_max'],
                ),
            ],
            'co_authors' => ['nullable', 'string', 'max:2000'],
            'manuscript' => array_merge(['required'], ManuscriptUpload::rules()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'manuscript.required' => 'Please upload a manuscript file in PDF or DOCX format.',
            'manuscript.mimes' => 'The manuscript must be a PDF or DOCX file.',
        ];
    }
}
