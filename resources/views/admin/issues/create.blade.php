<x-layouts.admin title="Add issue">
    <p class="text-sm text-slate-600">Volume and issue numbers are used on public article pages and in citations.</p>

    <form method="POST" action="{{ route('admin.issues.store') }}" class="mt-6 max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="volume_number" type="number" label="Volume number" value="{{ old('volume_number') }}" min="1" required />
            <x-form.input name="issue_number" type="number" label="Issue number" value="{{ old('issue_number') }}" min="1" required />
        </div>
        <x-form.input name="title" label="Title (optional)" value="{{ old('title') }}" placeholder="Special Issue on..." />
        <x-form.input name="publication_month_year" label="Publication month and year" value="{{ old('publication_month_year') }}" placeholder="June 2026" required />
        <x-form.checkbox name="is_special_issue" label="This is a special issue" :checked="old('is_special_issue', false)" />
        <x-form.input name="special_issue_theme" label="Special issue theme" value="{{ old('special_issue_theme') }}" placeholder="Shown on the public archive when this is a special issue" />
        <div class="flex gap-3">
            <x-form.button :full="false">Create issue</x-form.button>
            <a href="{{ route('admin.issues.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
