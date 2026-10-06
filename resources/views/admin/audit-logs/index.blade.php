<x-layouts.admin title="Audit log">
    <x-admin.page-header title="Audit log" subtitle="A permanent record of every administrative change. Entries cannot be edited or deleted." />

    <x-admin.filters placeholder="Search description or action…" :dates="true">
        <x-select name="action" label="Action" :options="$actions" :value="request('action')" placeholder="All actions" class="[&_label]:text-xs" />
        <x-select name="user" label="Staff member" :options="$staff" :value="request('user')" placeholder="All staff" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="created_at" label="When" /></th>
                        <th>Who</th>
                        <th><x-admin.sort-link column="action" label="Action" /></th>
                        <th>Description</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="text-xs whitespace-nowrap">{{ $log->created_at?->format('j M Y, g:i:sa') }}</td>
                            <td class="text-sm whitespace-nowrap">{{ $log->user?->name ?? ($log->user_id ? 'Deleted user #'.$log->user_id : 'System') }}</td>
                            <td><code class="rounded bg-ink-50 px-1.5 py-0.5 text-xs text-ink-700">{{ $log->action }}</code></td>
                            <td class="max-w-xl text-sm">
                                {{ $log->description }}
                                @if ($log->subject_type)
                                    <span class="block text-xs text-ink-400">{{ $log->subject_type }} #{{ $log->subject_id }}</span>
                                @endif
                                @if ($log->old_values || $log->new_values)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-medium text-gold-700 hover:text-gold-800">View changes</summary>
                                        <div class="mt-2 grid gap-2 lg:grid-cols-2">
                                            @if ($log->old_values)
                                                <div>
                                                    <p class="mb-1 text-xs font-semibold text-ink-500">Before</p>
                                                    <pre class="max-h-72 overflow-auto rounded-lg bg-ink-50 p-3 text-xs whitespace-pre-wrap break-all text-ink-700">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif
                                            @if ($log->new_values)
                                                <div>
                                                    <p class="mb-1 text-xs font-semibold text-ink-500">After</p>
                                                    <pre class="max-h-72 overflow-auto rounded-lg bg-ink-50 p-3 text-xs whitespace-pre-wrap break-all text-ink-700">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="text-xs whitespace-nowrap text-ink-500">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No audit entries" message="No entries match your filters." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-layouts.admin>
