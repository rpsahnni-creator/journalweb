<?php

namespace App\Http\Requests\Editorial;

use App\Models\Issue;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $issue instanceof Issue
            && ($this->user()?->can('update', $issue) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $issue = $this->route('issue');
        $journalId = CurrentJournal::managed()?->id;
        $volumeId = $this->integer('volume_id') ?: ($issue instanceof Issue ? $issue->volume_id : 0);

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
                Rule::unique('issues', 'number')
                    ->where(fn ($query) => $query->where('volume_id', $volumeId))
                    ->ignore($issue instanceof Issue ? $issue->id : null),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
