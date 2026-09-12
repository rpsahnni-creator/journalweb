<x-layouts.admin title="Audit log">
    <p class="text-slate-600">Important administrative changes are recorded here, including user, role, journal, board, and policy updates.</p>

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Action or record type" />
        <x-form.select
            name="action"
            label="Action"
            :options="['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted']"
            :selected="$filters['action'] ?? ''"
            placeholder="All actions"
        />
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">When</th>
                    <th class="px-4 py-3 font-medium">Actor</th>
                    <th class="px-4 py-3 font-medium">Action</th>
                    <th class="px-4 py-3 font-medium">Record</th>
                    <th class="px-4 py-3 font-medium">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 text-slate-500">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3">{{ $log->action }}</td>
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</p>
                            @if ($log->new_values)
                                <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit(json_encode($log->new_values), 80) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-600">No audit entries match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
