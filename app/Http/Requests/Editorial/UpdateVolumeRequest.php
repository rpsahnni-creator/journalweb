<?php

namespace App\Http\Requests\Editorial;

use App\Models\Volume;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVolumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $volume = $this->route('volume');

        return $volume instanceof Volume
            && ($this->user()?->can('update', $volume) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $volume = $this->route('volume');
        $journalId = CurrentJournal::managed()?->id;

        return [
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
                Rule::unique('volumes', 'number')
                    ->where(fn ($query) => $query->where('journal_id', $journalId))
                    ->ignore($volume instanceof Volume ? $volume->id : null),
            ],
            'year' => ['required', 'integer', 'min:1800', 'max:'.(now()->year + 5)],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
