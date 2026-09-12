<?php

namespace App\Http\Requests\Reviewer;

use App\Models\ReviewerAssignment;
use Illuminate\Foundation\Http\FormRequest;

class DeclineAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment instanceof ReviewerAssignment
            && ($this->user()?->can('respond', $assignment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
