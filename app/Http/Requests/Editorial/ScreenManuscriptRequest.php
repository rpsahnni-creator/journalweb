<?php

namespace App\Http\Requests\Editorial;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScreenManuscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Article
            && ($this->user()?->can('screen', $manuscript) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(['in_progress', 'send_to_review', 'reject'])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'comments_to_author' => ['nullable', 'required_if:outcome,reject', 'string', 'max:5000'],
        ];
    }
}
