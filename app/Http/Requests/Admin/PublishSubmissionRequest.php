<?php

namespace App\Http\Requests\Admin;

use App\Models\Issue;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class PublishSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission
            && ($this->user()?->can('publish', $submission) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue_id' => ['required', 'integer', Rule::exists('issues', 'id')],
            'page_start' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'page_end' => ['nullable', 'integer', 'min:1', 'max:9999', 'gte:page_start'],
            'pdf' => ['nullable', File::types(['pdf'])->max(20480)],
        ];
    }

    public function issue(): Issue
    {
        return Issue::query()->findOrFail($this->integer('issue_id'));
    }
}
