<?php

namespace App\Http\Requests\Admin;

use App\Models\Journal;
use App\Support\CurrentJournal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $journal = $this->route('journal') ?? CurrentJournal::managed();

        if ($journal instanceof Journal) {
            return $this->user()?->can('update', $journal) ?? false;
        }

        return $this->user()?->can('create', Journal::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $journal = $this->route('journal') ?? CurrentJournal::managed();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('journals', 'slug')->ignore($journal?->id)],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'issn' => ['nullable', 'string', 'max:9'],
            'eissn' => ['nullable', 'string', 'max:9'],
            'is_active' => ['sometimes', 'boolean'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_address' => ['nullable', 'string', 'max:1000'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:10000'],
            'aims_scope_text' => ['nullable', 'string', 'max:10000'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'publisher_address' => ['nullable', 'string', 'max:1000'],
            'publisher_email' => ['nullable', 'email', 'max:255'],
            'publisher_website' => ['nullable', 'url', 'max:255'],
            'publication_frequency' => ['nullable', 'string', 'max:255'],
            'indexing_status' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
