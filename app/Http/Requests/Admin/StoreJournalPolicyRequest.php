<?php

namespace App\Http\Requests\Admin;

use App\Enums\JournalPolicyType;
use App\Models\JournalPolicy;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JournalPolicy::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $journalId = CurrentJournal::managed()?->id;

        return [
            'type' => ['required', Rule::enum(JournalPolicyType::class)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('policies', 'slug')->where('journal_id', $journalId)],
            'body' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
