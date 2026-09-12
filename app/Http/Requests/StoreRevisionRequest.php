<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Support\ManuscriptUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission
            && ($this->user()?->can('uploadRevision', $submission) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'manuscript' => array_merge(['required'], ManuscriptUpload::rules()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'manuscript.required' => 'Please upload a revised manuscript in PDF or DOCX format.',
            'manuscript.mimes' => 'The manuscript must be a PDF or DOCX file.',
        ];
    }
}
