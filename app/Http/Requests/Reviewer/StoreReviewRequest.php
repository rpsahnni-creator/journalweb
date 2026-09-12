<?php

namespace App\Http\Requests\Reviewer;

use App\Enums\ReviewRecommendation;
use App\Models\ReviewerAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment instanceof ReviewerAssignment
            && ($this->user()?->can('submitReview', $assignment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recommendation' => ['required', Rule::enum(ReviewRecommendation::class)],
            'comments_to_author' => ['required', 'string', 'min:20', 'max:20000'],
            'comments_to_editor' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
