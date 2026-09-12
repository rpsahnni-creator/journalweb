<?php

namespace App\Http\Requests\Editorial;

use App\Models\Issue;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Issue::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $journalId = CurrentJournal::managed()?->id;

        return [
            'volume_id' => [
                'required',
                'integer',
                Rule::exists('volumes', 'id')->where(fn ($query) => $query->where('journal_id', $journalId)),
            ],
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('issues', 'number')->where(fn ($query) => $query->where('volume_id', $this->integer('volume_id'))),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
