<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission
            && ($this->user()?->can('update', $submission) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SubmissionStatus::class)],
            'editor_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
