<?php

namespace App\Http\Requests\Admin;

use App\Enums\JournalPolicyType;
use App\Models\JournalPolicy;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('policy');

        return $policy instanceof JournalPolicy
            && ($this->user()?->can('update', $policy) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $policy = $this->route('policy');
        $journalId = $policy instanceof JournalPolicy ? $policy->journal_id : CurrentJournal::managed()?->id;

        return [
            'type' => ['required', Rule::enum(JournalPolicyType::class)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('policies', 'slug')->where('journal_id', $journalId)->ignore($policy?->id),
            ],
            'body' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
