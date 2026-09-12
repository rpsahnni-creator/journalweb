<?php

namespace App\Http\Requests\Editorial;

use App\Enums\EditorialDecisionType;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEditorialDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manuscript = $this->route('manuscript');

        return $manuscript instanceof Article
            && ($this->user()?->can('issueDecision', $manuscript) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::enum(EditorialDecisionType::class)->only(EditorialDecisionType::postReviewCases())],
            'comments_to_author' => ['required', 'string', 'min:20', 'max:20000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'revision_due_at' => ['nullable', 'date', 'after:today', 'required_if:decision,minor_revision,major_revision'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $manuscript = $this->route('manuscript');
            $decision = EditorialDecisionType::tryFrom((string) $this->input('decision'));

            if (! $manuscript instanceof Article || $decision === null) {
                return;
            }

            $to = $decision->resultingStatus();

            if ($to !== null && ! $manuscript->status->canTransitionTo($to)) {
                $validator->errors()->add('decision', 'That editorial decision is not valid from the current manuscript status.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comments_to_author.required' => 'A decision letter to the author is required.',
            'revision_due_at.required_if' => 'Set a revision deadline when requesting a revision.',
        ];
    }
}
