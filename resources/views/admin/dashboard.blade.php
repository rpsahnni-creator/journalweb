<x-layouts.admin title="Dashboard">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <p class="text-sm text-slate-600">Operational overview and system metrics. Unpublished manuscripts remain strictly isolated from public views.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                System Healthy
            </span>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Users</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <x-icon name="users" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $userCount }}</p>
            <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span>{{ $activeUserCount }} active accounts</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Editorial board</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <x-icon name="user-cog" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $boardCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Listed members</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Policies</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <x-icon name="file-text" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $policyCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Published guidelines</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Published articles</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-icon name="book-open" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $publishedArticleCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Live in public record</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Volumes</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <x-icon name="library" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $volumeCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Journal volumes</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Issues</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <x-icon name="layers" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $issueCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Compiled issues</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Contact messages</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <x-icon name="mail" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $contactCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Inquiries received</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Journal</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <x-icon name="globe" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-lg font-bold text-slate-900 truncate" title="{{ $journal?->name ?? 'Not created' }}">{{ $journal?->name ?? 'Not created' }}</p>
            <p class="mt-2 text-xs text-slate-500 truncate">{{ $journal?->abbreviation ?: 'Primary journal instance' }}</p>
        </div>
    </div>

    <!-- Analytics & Audit Grid -->
    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs xl:col-span-1">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                        <x-icon name="pie-chart" class="h-3.5 w-3.5" />
                    </div>
                    <h2 class="font-semibold text-slate-900 text-sm">Users by role</h2>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ $userCount }} total</span>
            </div>
            <div class="mt-4">
                <canvas
                    x-data
                    x-init="
                        new Chart($el, {
                            type: 'bar',
                            data: {
                                labels: @json($roleChart->pluck('label')),
                                datasets: [{
                                    label: 'Users',
                                    data: @json($roleChart->pluck('count')),
                                    backgroundColor: '#1b3654',
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { precision: 0 },
                                        grid: { color: '#f1f5f9' }
                                    },
                                    x: {
                                        grid: { display: false }
                                    }
                                }
                            }
                        })
                    "
                ></canvas>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                        <x-icon name="scroll-text" class="h-3.5 w-3.5" />
                    </div>
                    <h2 class="font-semibold text-slate-900 text-sm">Recent audit activity</h2>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                    <span>View full audit log</span>
                    <x-icon name="chevron-right" class="h-3 w-3" />
                </a>
            </div>
            @if ($recentLogs->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    <x-icon name="check-circle" class="mx-auto h-8 w-8 text-slate-300" />
                    <p class="mt-2">No audited changes have been recorded yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-slate-50/75 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3">Timestamp</th>
                                <th class="px-5 py-3">Actor</th>
                                <th class="px-5 py-3">Action</th>
                                <th class="px-5 py-3">Target Record</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentLogs as $log)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-3 font-mono text-slate-500 whitespace-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-slate-600">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
