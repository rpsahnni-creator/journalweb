<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateJournalSettingsRequest;
use App\Models\Journal;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use App\Support\JournalCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JournalSettingsController extends Controller
{
    public function edit(): View
    {
        $journal = CurrentJournal::managed();

        if ($journal) {
            $this->authorize('update', $journal);
        } else {
            $this->authorize('create', Journal::class);
        }

        return view('admin.journal.edit', [
            'journal' => $journal,
            'contactEmail' => $journal?->setting('contact_email'),
            'contactAddress' => $journal?->setting('contact_address'),
            'seoDescription' => $journal?->setting('seo_description'),
            'aboutText' => $journal?->setting('about_text'),
            'aimsScopeText' => $journal?->setting('aims_scope_text'),
            'publisherName' => $journal?->setting('publisher_name') ?: $journal?->publisher,
            'publisherAddress' => $journal?->setting('publisher_address'),
            'publisherEmail' => $journal?->setting('publisher_email'),
            'publisherWebsite' => $journal?->setting('publisher_website') ?: JournalCopy::PUBLISHER_WEBSITE,
            'publicationFrequency' => $journal?->setting('publication_frequency') ?: JournalCopy::PUBLICATION_FREQUENCY,
            'indexingStatus' => $journal?->setting('indexing_status') ?: JournalCopy::INDEXING_STATUS,
            'issn' => $journal?->setting('issn') ?: $journal?->issn,
        ]);
    }

    public function update(UpdateJournalSettingsRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::managed();
        $data = $request->safe()->only([
            'name', 'slug', 'abbreviation', 'description', 'publisher',
            'website_url', 'issn', 'eissn',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        foreach (['issn', 'eissn'] as $field) {
            if (! $request->exists($field)) {
                unset($data[$field]);

                continue;
            }

            $data[$field] = $request->input($field) ?: null;
        }

        if ($journal === null) {
            $this->authorize('create', Journal::class);
            $journal = Journal::query()->create($data);
            $this->syncSettings($journal, $request);
            Auditor::log('created', $journal, null, $journal->only(['name', 'slug']), $journal->id);

            return redirect()->route('admin.journal.edit')->with('status', 'Journal created.');
        }

        $this->authorize('update', $journal);
        $old = $journal->only(['name', 'slug', 'description', 'is_active']);
        $journal->update($data);
        $this->syncSettings($journal, $request);
        Auditor::log('updated', $journal->fresh(), $old, $journal->only(['name', 'slug', 'description', 'is_active']), $journal->id);

        return redirect()->route('admin.journal.edit')->with('status', 'Journal settings saved.');
    }

    private function syncSettings(Journal $journal, UpdateJournalSettingsRequest $request): void
    {
        $settings = [
            'contact_email' => $request->input('contact_email'),
            'contact_address' => $request->input('contact_address'),
            'seo_description' => $request->input('seo_description'),
            'about_text' => $request->input('about_text'),
            'aims_scope_text' => $request->input('aims_scope_text'),
            'publisher_name' => $request->input('publisher_name'),
            'publisher_address' => $request->input('publisher_address'),
            'publisher_email' => $request->input('publisher_email'),
            'publisher_website' => $request->input('publisher_website'),
            'publication_frequency' => $request->input('publication_frequency'),
            'indexing_status' => $request->input('indexing_status'),
        ];

        foreach ($settings as $key => $value) {
            if (! $request->exists($key)) {
                continue;
            }

            $journal->settings()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $journal->settings()->updateOrCreate(
            ['key' => 'issn'],
            ['value' => $journal->issn]
        );
    }
}
