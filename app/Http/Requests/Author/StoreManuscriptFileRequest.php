<?php

namespace App\Http\Requests\Author;

use App\Enums\ArticleFileType;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManuscriptFileRequest extends FormRequest
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
        $type = ArticleFileType::tryFrom((string) $this->input('type', ArticleFileType::Manuscript->value))
            ?? ArticleFileType::Manuscript;
        $configKey = match ($type) {
            ArticleFileType::Manuscript => 'manuscript',
            ArticleFileType::CoverLetter => 'cover_letter',
            default => 'supplementary',
        };
        $config = config('manuscripts.'.$configKey);

        return [
            'type' => ['required', Rule::in([
                ArticleFileType::Manuscript->value,
                ArticleFileType::Supplementary->value,
                ArticleFileType::CoverLetter->value,
            ])],
            'file' => [
                'required',
                'file',
                'max:'.$config['max_kilobytes'],
                'mimes:'.implode(',', $config['extensions']),
                'mimetypes:'.implode(',', $config['mimetypes']),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $upload = $this->file('file');

            if ($upload === null || $validator->errors()->has('file')) {
                return;
            }

            $type = ArticleFileType::tryFrom((string) $this->input('type')) ?? ArticleFileType::Supplementary;
            $configKey = match ($type) {
                ArticleFileType::Manuscript => 'manuscript',
                ArticleFileType::CoverLetter => 'cover_letter',
                default => 'supplementary',
            };
            $extension = strtolower((string) $upload->getClientOriginalExtension());
            $allowed = config('manuscripts.'.$configKey.'.extensions', []);

            if (! in_array($extension, $allowed, true)) {
                $validator->errors()->add('file', 'That file extension is not allowed.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'The file type is not allowed.',
            'file.mimetypes' => 'The file MIME type is not allowed.',
            'file.max' => 'The file is larger than the permitted size.',
        ];
    }
}
