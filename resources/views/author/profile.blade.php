<x-layouts.app title="Author profile">
    <x-author-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Author profile</h1>
    <p class="mt-2 text-slate-600">Your name and affiliation appear on manuscript submissions. Affiliation is required before you can submit.</p>

    <form method="POST" action="{{ route('author.profile.update') }}" class="mt-8 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Full name" value="{{ old('name', $user->name) }}" required />
        <x-form.input name="email" type="email" label="Email" value="{{ old('email', $user->email) }}" required />
        <x-form.input name="academic_title" label="Academic title" value="{{ old('academic_title', $user->academic_title) }}" />
        <x-form.input name="affiliation" label="Affiliation" value="{{ old('affiliation', $user->affiliation) }}" required />
        <x-form.input name="orcid" label="ORCID" value="{{ old('orcid', $user->orcid) }}" placeholder="0000-0000-0000-0000" />
        <x-form.textarea name="biography" label="Biography" rows="4">{{ old('biography', $user->biography) }}</x-form.textarea>
        <x-form.button :full="false">Save author profile</x-form.button>
    </form>
</x-layouts.app>
