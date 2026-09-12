<?php

namespace App\Http\Requests\Editorial;

use App\Models\Volume;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVolumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Volume::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $journalId = CurrentJournal::managed()?->id;

        return [
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
                Rule::unique('volumes', 'number')->where(fn ($query) => $query->where('journal_id', $journalId)),
            ],
            'year' => ['required', 'integer', 'min:1800', 'max:'.(now()->year + 5)],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
