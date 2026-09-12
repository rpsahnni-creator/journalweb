<?php

namespace App\Http\Requests\Reviewer;

use App\Enums\ReviewRecommendation;
use App\Models\SubmissionReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('submissionReview');

        return $review instanceof SubmissionReview
            && $this->user() !== null
            && $this->user()->is_reviewer === true
            && $review->isAssignedTo($this->user())
            && ! $review->isSubmitted();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recommendation' => ['required', Rule::enum(ReviewRecommendation::class)],
            'comments_to_editor' => ['nullable', 'string', 'max:10000'],
            'comments_to_author' => ['required', 'string', 'min:20', 'max:10000'],
        ];
    }
}
