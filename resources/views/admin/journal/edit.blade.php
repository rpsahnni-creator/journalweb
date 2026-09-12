<x-layouts.admin title="Journal settings">
    <p class="text-slate-600">These values appear on the public site. ISSN fields are optional and should stay empty until a real identifier is assigned.</p>

    <form method="POST" action="{{ route('admin.journal.update') }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" label="Journal name" value="{{ old('name', $journal?->name) }}" required />
        <x-form.input name="slug" label="Slug" value="{{ old('slug', $journal?->slug) }}" required />
        <x-form.input name="abbreviation" label="Abbreviation" value="{{ old('abbreviation', $journal?->abbreviation) }}" />
        <x-form.textarea name="description" label="Description" rows="5">{{ old('description', $journal?->description) }}</x-form.textarea>
        <x-form.input name="publisher" label="Publisher" value="{{ old('publisher', $journal?->publisher) }}" />
        <x-form.input name="website_url" type="url" label="Website URL" value="{{ old('website_url', $journal?->website_url) }}" />
        <x-form.input name="issn" label="Print ISSN" value="{{ old('issn', $issn ?? $journal?->issn) }}" placeholder="Leave blank until an ISSN is assigned" />
        <x-form.input name="eissn" label="Electronic ISSN" value="{{ old('eissn', $journal?->eissn) }}" placeholder="Leave blank if none" />

        <x-form.input name="contact_email" type="email" label="Contact email" value="{{ old('contact_email', $contactEmail) }}" />
        <x-form.textarea name="contact_address" label="Contact address" rows="3">{{ old('contact_address', $contactAddress) }}</x-form.textarea>
        <x-form.textarea name="seo_description" label="Default SEO description" rows="3">{{ old('seo_description', $seoDescription) }}</x-form.textarea>
        <x-form.textarea name="about_text" label="About the journal" rows="6">{{ old('about_text', $aboutText) }}</x-form.textarea>
        <x-form.textarea name="aims_scope_text" label="Aims and scope" rows="6">{{ old('aims_scope_text', $aimsScopeText) }}</x-form.textarea>
        <x-form.input name="publisher_name" label="Publisher name" value="{{ old('publisher_name', $publisherName) }}" />
        <x-form.textarea name="publisher_address" label="Publisher address" rows="3">{{ old('publisher_address', $publisherAddress) }}</x-form.textarea>
        <x-form.input name="publisher_email" type="email" label="Publisher email" value="{{ old('publisher_email', $publisherEmail) }}" />
        <x-form.input name="publisher_website" type="url" label="Publisher website" value="{{ old('publisher_website', $publisherWebsite ?? '') }}" placeholder="https://srtc.ac.in" />
        <x-form.input name="publication_frequency" label="Publication frequency" value="{{ old('publication_frequency', $publicationFrequency) }}" placeholder="e.g. Biannual — published every June and December." />
        <x-form.textarea name="indexing_status" label="Indexing status" rows="4">{{ old('indexing_status', $indexingStatus ?? '') }}</x-form.textarea>

        <x-form.checkbox name="is_active" label="Journal is active on the public site" :checked="old('is_active', $journal?->is_active ?? true)" />

        <x-form.button :full="false">{{ $journal ? 'Save settings' : 'Create journal' }}</x-form.button>
    </form>
</x-layouts.admin>
