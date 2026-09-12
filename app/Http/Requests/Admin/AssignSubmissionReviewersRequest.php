<?php

namespace App\Http\Requests\Admin;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignSubmissionReviewersRequest extends FormRequest
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
        $submission = $this->route('submission');
        $authorId = $submission instanceof Submission ? $submission->user_id : 0;

        return [
            'reviewer_ids' => ['required', 'array', 'min:1'],
            'reviewer_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($authorId): void {
                    $query->where('is_reviewer', true)
                        ->where('is_active', true)
                        ->where('id', '!=', $authorId);
                }),
            ],
        ];
    }
}
